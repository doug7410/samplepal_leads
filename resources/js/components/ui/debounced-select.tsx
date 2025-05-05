import React, { useState, useEffect, useRef, useCallback } from 'react';
import {
  Select as BaseSelect,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

interface DebouncedSelectProps {
  value: string;
  onValueChange: (value: string) => void;
  placeholder?: string;
  options: Array<{ value: string; label: string }>;
  debounceTime?: number;
  className?: string;
  disabled?: boolean;
}

export function DebouncedSelect({
  value: externalValue,
  onValueChange,
  placeholder,
  options,
  debounceTime = 300,
  className,
  disabled = false,
}: DebouncedSelectProps) {
  // Internal state for immediate UI feedback
  const [internalValue, setInternalValue] = useState(externalValue);
  const timerRef = useRef<NodeJS.Timeout | null>(null);

  // Update internal value when external value changes
  useEffect(() => {
    setInternalValue(externalValue);
  }, [externalValue]);

  // Handle value changes with debounce
  const handleValueChange = useCallback(
    (newValue: string) => {
      // Update internal state immediately for responsive UI
      setInternalValue(newValue);
      
      // Clear any existing timer
      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }
      
      // Set a new timer to update the external state after the debounce time
      timerRef.current = setTimeout(() => {
        onValueChange(newValue);
      }, debounceTime);
    },
    [onValueChange, debounceTime]
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
    <BaseSelect 
      value={internalValue} 
      onValueChange={handleValueChange}
      disabled={disabled}
    >
      <SelectTrigger className={className}>
        <SelectValue placeholder={placeholder} />
      </SelectTrigger>
      <SelectContent>
        {options.map((option) => (
          <SelectItem key={option.value} value={option.value}>
            {option.label}
          </SelectItem>
        ))}
      </SelectContent>
    </BaseSelect>
  );
}