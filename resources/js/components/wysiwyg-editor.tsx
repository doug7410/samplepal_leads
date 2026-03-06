import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import Underline from '@tiptap/extension-underline';
import Link from '@tiptap/extension-link';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import * as Toggle from '@radix-ui/react-toggle';
import hljs from 'highlight.js/lib/core';
import xml from 'highlight.js/lib/languages/xml';
import 'highlight.js/styles/github.css';
import { useEffect, useState } from 'react';
import CodeEditor from 'react-simple-code-editor';

hljs.registerLanguage('xml', xml);

function formatHtml(html: string): string {
    let depth = 0;
    const voidTags = /^<(area|base|br|col|embed|hr|img|input|link|meta|param|source|track|wbr)/i;
    return html
        .replace(/></g, '>\n<')
        .split('\n')
        .map((line) => {
            if (line.match(/^<\/\w/)) depth = Math.max(0, depth - 1);
            const indent = '  '.repeat(depth);
            if (line.match(/^<\w/) && !line.match(/^<\//) && !voidTags.test(line) && !line.endsWith('/>')) depth++;
            return indent + line;
        })
        .join('\n');
}

interface WysiwygEditorProps {
    value: string;
    onChange: (content: string) => void;
    placeholder?: string;
    height?: number;
    error?: boolean;
}

function ToolbarButton({ active, onClick, children }: { active?: boolean; onClick: () => void; children: React.ReactNode }) {
    return (
        <Toggle.Root
            pressed={active}
            onPressedChange={onClick}
            className={`rounded px-2 py-1 text-sm font-medium transition-colors ${
                active ? 'bg-gray-200 text-gray-900' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
            }`}
        >
            {children}
        </Toggle.Root>
    );
}

export default function WysiwygEditor({
    value,
    onChange,
    placeholder = 'Write your email content here...',
    height = 400,
    error = false,
}: WysiwygEditorProps) {
    const [mode, setMode] = useState<'rich' | 'html'>('rich');
    const [rawHtml, setRawHtml] = useState(value);

    const editor = useEditor({
        extensions: [
            StarterKit,
            Underline,
            Link.configure({ openOnClick: false }),
            Image,
            Placeholder.configure({ placeholder }),
        ],
        content: value,
        onUpdate({ editor }) {
            const html = editor.getHTML();
            setRawHtml(html);
            onChange(html);
        },
    });

    useEffect(() => {
        if (editor && value !== editor.getHTML()) {
            editor.commands.setContent(value);
            setRawHtml(value);
        }
    }, [value]);

    function switchToHtml() {
        if (editor) setRawHtml(formatHtml(editor.getHTML()));
        setMode('html');
    }

    function switchToRich() {
        if (editor) editor.commands.setContent(rawHtml);
        onChange(rawHtml);
        setMode('rich');
    }

    return (
        <div className={`rounded-md border ${error ? 'border-red-500' : 'border-input'} overflow-hidden bg-white`}>
            <div className="flex flex-wrap items-center gap-1 border-b bg-gray-50 px-3 py-2">
                {mode === 'rich' && (
                    <>
                        <ToolbarButton active={editor?.isActive('bold')} onClick={() => editor?.chain().focus().toggleBold().run()}>
                            <strong>B</strong>
                        </ToolbarButton>
                        <ToolbarButton active={editor?.isActive('italic')} onClick={() => editor?.chain().focus().toggleItalic().run()}>
                            <em>I</em>
                        </ToolbarButton>
                        <ToolbarButton active={editor?.isActive('underline')} onClick={() => editor?.chain().focus().toggleUnderline().run()}>
                            <span className="underline">U</span>
                        </ToolbarButton>
                        <div className="mx-1 w-px bg-gray-300" />
                        <ToolbarButton
                            active={editor?.isActive('heading', { level: 1 })}
                            onClick={() => editor?.chain().focus().toggleHeading({ level: 1 }).run()}
                        >
                            H1
                        </ToolbarButton>
                        <ToolbarButton
                            active={editor?.isActive('heading', { level: 2 })}
                            onClick={() => editor?.chain().focus().toggleHeading({ level: 2 }).run()}
                        >
                            H2
                        </ToolbarButton>
                        <div className="mx-1 w-px bg-gray-300" />
                        <ToolbarButton active={editor?.isActive('bulletList')} onClick={() => editor?.chain().focus().toggleBulletList().run()}>
                            • List
                        </ToolbarButton>
                        <ToolbarButton active={editor?.isActive('orderedList')} onClick={() => editor?.chain().focus().toggleOrderedList().run()}>
                            1. List
                        </ToolbarButton>
                        <div className="mx-1 w-px bg-gray-300" />
                        <ToolbarButton active={editor?.isActive('blockquote')} onClick={() => editor?.chain().focus().toggleBlockquote().run()}>
                            ❝
                        </ToolbarButton>
                        <div className="mx-1 w-px bg-gray-300" />
                        <ToolbarButton
                            onClick={() => {
                                const url = window.prompt('Image URL');
                                if (url) editor?.chain().focus().setImage({ src: url }).run();
                            }}
                        >
                            IMG
                        </ToolbarButton>
                        <div className="mx-1 w-px bg-gray-300" />
                    </>
                )}
                <button
                    type="button"
                    onClick={mode === 'rich' ? switchToHtml : switchToRich}
                    className="ml-auto rounded px-2 py-1 text-xs font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-900"
                >
                    {mode === 'rich' ? 'HTML' : 'Rich Text'}
                </button>
            </div>

            {mode === 'rich' ? (
                <EditorContent
                    editor={editor}
                    className="wysiwyg-editor px-4 py-3 focus-within:outline-none"
                    style={{ minHeight: height + 'px', maxHeight: height + 'px', overflowY: 'auto' }}
                />
            ) : (
                <div style={{ minHeight: height + 'px', maxHeight: height + 'px', overflowY: 'auto' }}>
                    <CodeEditor
                        value={rawHtml}
                        onValueChange={(code) => {
                            setRawHtml(code);
                            onChange(code);
                        }}
                        highlight={(code) => hljs.highlight(code, { language: 'xml' }).value}
                        padding={16}
                        style={{ fontFamily: 'ui-monospace, monospace', fontSize: 13, minHeight: height + 'px' }}
                    />
                </div>
            )}
        </div>
    );
}
