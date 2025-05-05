import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

interface DebouncedInputProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'onChange'> {
  value: string;
  onChange: (value: string) => void;
  debounceTime?: number;
}

export function DebouncedInput({
  value: externalValue,
  onChange,
  debounceTime = 300,
  className,
  ...props
}: DebouncedInputProps) {
  // Internal state for immediate UI feedback
  const [internalValue, setInternalValue] = useState(externalValue);
  const timerRef = useRef<NodeJS.Timeout | null>(null);

  // Update internal value when external value changes
  useEffect(() => {
    setInternalValue(externalValue);
  }, [externalValue]);

  // Handle input changes with debounce
  const handleChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      const newValue = e.target.value;
      
      // Update internal state immediately for responsive UI
      setInternalValue(newValue);
      
      // Clear any existing timer
      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }
      
      // Set a new timer to update the external state after the debounce time
      timerRef.current = setTimeout(() => {
        onChange(newValue);
      }, debounceTime);
    },
    [onChange, debounceTime]
  );

  // Clean up timer on unmount
  useEffect(() => {
    return () => {
      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }
    };
  }, []);

  return (
    <Input
      value={internalValue}
      onChange={handleChange}
      className={cn(className)}
      {...props}
    />
  );
}