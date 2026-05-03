import type { MDXComponents } from 'mdx/types';
import Link from 'next/link';

export function useMDXComponents(components: MDXComponents): MDXComponents {
    return {
        h1: ({ children }) => (
            <h1 className="text-3xl font-bold text-gray-900 mb-6 pb-4 border-b border-gray-200">
                {children}
            </h1>
        ),
        h2: ({ children }) => (
            <h2 className="text-2xl font-bold text-gray-900 mt-10 mb-4">
                {children}
            </h2>
        ),
        h3: ({ children }) => (
            <h3 className="text-xl font-semibold text-gray-800 mt-8 mb-3">
                {children}
            </h3>
        ),
        p: ({ children }) => (
            <p className="text-gray-600 leading-relaxed mb-4">
                {children}
            </p>
        ),
        ul: ({ children }) => (
            <ul className="list-disc list-inside space-y-2 mb-4 text-gray-600">
                {children}
            </ul>
        ),
        ol: ({ children }) => (
            <ol className="list-decimal list-inside space-y-2 mb-4 text-gray-600">
                {children}
            </ol>
        ),
        li: ({ children }) => (
            <li className="leading-relaxed">
                {children}
            </li>
        ),
        a: ({ href, children }) => (
            <Link
                href={href || '#'}
                className="text-[#ea580c] hover:text-[#c2410c] underline underline-offset-2"
            >
                {children}
            </Link>
        ),
        code: ({ children }) => (
            <code className="bg-gray-100 text-gray-800 px-1.5 py-0.5 rounded text-sm font-mono">
                {children}
            </code>
        ),
        pre: ({ children }) => (
            <pre className="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto mb-4">
                {children}
            </pre>
        ),
        blockquote: ({ children }) => (
            <blockquote className="border-l-4 border-[#ea580c] pl-4 py-2 my-4 bg-[#ffedd5] rounded-r-lg">
                {children}
            </blockquote>
        ),
        strong: ({ children }) => (
            <strong className="font-semibold text-gray-900">
                {children}
            </strong>
        ),
        table: ({ children }) => (
            <div className="overflow-x-auto mb-4">
                <table className="min-w-full divide-y divide-gray-200">
                    {children}
                </table>
            </div>
        ),
        th: ({ children }) => (
            <th className="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider bg-gray-50">
                {children}
            </th>
        ),
        td: ({ children }) => (
            <td className="px-4 py-3 text-sm text-gray-600 border-t border-gray-100">
                {children}
            </td>
        ),
        ...components,
    };
}
