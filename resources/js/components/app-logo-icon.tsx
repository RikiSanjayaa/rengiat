import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
        >
            <rect
                x="5"
                y="4"
                width="14"
                height="17"
                rx="3"
                stroke="currentColor"
                strokeWidth="2.2"
            />
            <path
                d="M9 4V3.5C9 2.67 9.67 2 10.5 2H13.5C14.33 2 15 2.67 15 3.5V4"
                stroke="currentColor"
                strokeWidth="2.2"
                strokeLinecap="round"
            />
            <path
                d="M8.5 9H15.5"
                stroke="currentColor"
                strokeWidth="2.2"
                strokeLinecap="round"
            />
            <path
                d="M8.5 13H13"
                stroke="currentColor"
                strokeWidth="2.2"
                strokeLinecap="round"
            />
            <path
                d="M13.5 16.5L15.2 18.2L18.5 14.9"
                stroke="currentColor"
                strokeWidth="2.2"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
