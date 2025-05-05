import React, { useState, useEffect } from 'react';
import { Label } from './ui/label';
import { DebouncedTextarea } from './ui/debounced-textarea';

interface NotesFieldProps {
  initialValue: string;
  onValueChange: (value: string) => void;
  error?: string;
  id?: string;
  label?: string;
  placeholder?: string;
  rows?: number;
}

/**
 * A standalone notes field component that manages its own state
 * and only updates the parent component when the value has stabilized
 */
export function NotesField({
  initialValue = '',
  onValueChange,
  error,
  id = 'notes',
  label = 'Notes',
  placeholder = 'Add any notes here...',
  rows = 4
}: NotesFieldProps) {
  // Track initialization to avoid sending initial value as a change
  const [initialized, setInitialized] = useState(false);
  
  // Update parent component when value changes
  useEffect(() => {
    if (initialized) {
      // This is intentionally empty - we'll only update the parent component
      // through the debounced onChange handler
    } else {
      setInitialized(true);
    }
  }, [initialized]);

  return (
    <div className="space-y-2">
      <Label htmlFor={id}>{label}</Label>
      <DebouncedTextarea
        id={id}
        name={id}
        value={initialValue}
        onChange={onValueChange}
        placeholder={placeholder}
        rows={rows}
        debounceTime={500} // Increased debounce time for larger text inputs
      />
      {error && <p className="text-sm text-red-500">{error}</p>}
    </div>
  );
}