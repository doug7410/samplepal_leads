import * as React from "react";
import { Textarea } from "./textarea";
import { cn } from "@/lib/utils";

interface DebouncedTextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  value: string;
  onChange: (value: string) => void;
  debounceTime?: number;
  className?: string;
}

export function DebouncedTextarea({
  value: externalValue,
  onChange,
  debounceTime = 300,
  className,
  ...props
}: DebouncedTextareaProps) {
  // Internal state for immediate UI feedback
  const [internalValue, setInternalValue] = React.useState(externalValue);
  const timerRef = React.useRef<NodeJS.Timeout | null>(null);

  // Update internal value when external value changes
  React.useEffect(() => {
    setInternalValue(externalValue);
  }, [externalValue]);

  // Handle input changes with debounce
  const handleChange = React.useCallback(
    (e: React.ChangeEvent<HTMLTextAreaElement>) => {
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
  React.useEffect(() => {
    return () => {
      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }
    };
  }, []);

  return (
    <Textarea
      value={internalValue}
      onChange={handleChange}
      className={cn("min-h-[120px] resize-y", className)}
      {...props}
    />
  );
}