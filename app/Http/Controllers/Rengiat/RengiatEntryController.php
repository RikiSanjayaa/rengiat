<?php

namespace App\Http\Controllers\Rengiat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rengiat\StoreRengiatEntryRequest;
use App\Http\Requests\Rengiat\UpdateRengiatEntryRequest;
use App\Models\RengiatAttachment;
use App\Models\RengiatEntry;
use App\Services\AttachmentImageProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class RengiatEntryController extends Controller
{
    public function __construct(private readonly AttachmentImageProcessor $attachmentImageProcessor) {}

    public function store(StoreRengiatEntryRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $unitId = $this->resolveTargetUnitId($validated['unit_id'] ?? null, $user->isOperator() ? $user->unit_id : null);

        $this->authorize('create', [RengiatEntry::class, $unitId]);

        DB::transaction(function () use ($request, $validated, $unitId, $user): void {
            $entry = RengiatEntry::create([
                'unit_id' => $unitId,
                'entry_date' => $validated['entry_date'],
                'time_start' => $validated['time_start'] ?? null,
                'description' => $validated['description'],
                'case_number' => null,
                'created_by' => $user->id,
                'updated_by' => null,
            ]);

            $this->storeAttachmentIfProvided($request, $entry);
        });

        return back()->with('success', 'Entry berhasil ditambahkan.');
    }

    public function update(UpdateRengiatEntryRequest $request, RengiatEntry $rengiatEntry): RedirectResponse
    {
        $this->authorize('update', $rengiatEntry);

        $validated = $request->validated();
        $user = $request->user();

        $targetUnitId = $user->isOperator()
            ? $rengiatEntry->unit_id
            : ($validated['unit_id'] ?? $rengiatEntry->unit_id);

        DB::transaction(function () use ($request, $validated, $targetUnitId, $rengiatEntry, $user): void {
            $rengiatEntry->fill([
                'unit_id' => $targetUnitId,
                'entry_date' => $validated['entry_date'],
                'time_start' => $validated['time_start'] ?? null,
                'description' => $validated['description'],
                'case_number' => null,
                'updated_by' => $user->id,
            ]);

            $rengiatEntry->save();
            $this->storeAttachmentIfProvided($request, $rengiatEntry);
        });

        return back()->with('success', 'Entry berhasil diperbarui.');
    }

    public function destroy(RengiatEntry $rengiatEntry): RedirectResponse
    {
        $this->authorize('delete', $rengiatEntry);

        DB::transaction(function () use ($rengiatEntry): void {
            foreach ($rengiatEntry->attachments as $attachment) {
                Storage::disk('public')->delete($attachment->path);
            }

            $rengiatEntry->delete();
        });

        return back()->with('success', 'Entry berhasil dihapus.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveTargetUnitId(?int $requestedUnitId, ?int $operatorUnitId): int
    {
        $resolvedUnitId = $operatorUnitId ?? $requestedUnitId;

        if ($resolvedUnitId === null) {
            throw ValidationException::withMessages([
                'unit_id' => 'Unit wajib dipilih.',
            ]);
        }

        return $resolvedUnitId;
    }

    private function storeAttachmentIfProvided(StoreRengiatEntryRequest|UpdateRengiatEntryRequest $request, RengiatEntry $entry): void
    {
        if (! config('rengiat.enable_attachments') || ! $request->hasFile('attachment')) {
            return;
        }

        try {
            $processed = $this->attachmentImageProcessor->processAndStore($request->file('attachment'));
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'attachment' => $exception->getMessage(),
            ]);
        }

        RengiatAttachment::create([
            'entry_id' => $entry->id,
            'path' => $processed['path'],
            'mime_type' => $processed['mime_type'],
            'size_bytes' => $processed['size_bytes'],
            'created_at' => now(),
        ]);
    }
}
