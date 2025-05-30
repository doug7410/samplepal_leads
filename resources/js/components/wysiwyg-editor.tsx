import { useEffect, useState } from 'react';
import { Label } from '@/components/ui/label';

interface WysiwygEditorProps {
  value: string;
  onChange: (content: string) => void;
  placeholder?: string;
  height?: number;
  error?: boolean;
}

export default function WysiwygEditor({ 
  value, 
  onChange, 
  placeholder = 'Write your HTML email content here...',
  height = 400,
  error = false
}: WysiwygEditorProps) {
  const [content, setContent] = useState(value);

  useEffect(() => {
    setContent(value);
  }, [value]);

  const handleChange = (e: React.ChangeEvent<HTMLTextAreaElement>) => {
    const newContent = e.target.value;
    setContent(newContent);
    onChange(newContent);
  };

  return (
    <div className={`rounded-md border ${error ? 'border-red-500' : 'border-input'} overflow-hidden bg-white`}>
      <div className="border-b px-3 py-2 bg-gray-50">
        <div className="text-sm font-medium">HTML Editor</div>
      </div>
      <textarea
        value={content}
        onChange={handleChange}
        className="w-full px-4 py-3 font-mono text-sm focus:outline-none resize-none wysiwyg-source"
        style={{ 
          minHeight: height + 'px',
          maxHeight: height + 'px',
          overflowY: 'auto'
        }}
        placeholder={placeholder}
      />
      <div className="border-t px-3 py-2 bg-gray-50">
        <div className="text-xs text-gray-600">
          Tip: You can use HTML tags like &lt;b&gt;, &lt;i&gt;, &lt;u&gt;, &lt;br&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, etc.
        </div>
      </div>
    </div>
  );
}