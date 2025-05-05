import React from 'react';
import { ChevronLeft, ChevronRight, MoreHorizontal } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export interface PaginationProps {
  total: number;
  perPage: number;
  currentPage: number;
  from: number;
  to: number;
  links: Array<{
    url: string | null;
    label: string;
    active: boolean;
  }>;
  onPageChange?: (page: number) => void;
  preserveScroll?: boolean;
  preserveState?: boolean;
  only?: string[];
  className?: string;
}

export function Pagination({
  total,
  perPage,
  currentPage,
  from,
  to,
  links,
  onPageChange,
  preserveScroll = true,
  preserveState = true,
  only = [],
  className,
}: PaginationProps) {
  const totalPages = Math.ceil(total / perPage);
  
  // Extract numbers from labels and filter out non-numeric links except prev/next
  const pageLinks = links.filter(link => 
    !isNaN(Number(link.label)) || 
    link.label === "&laquo; Previous" || 
    link.label === "Next &raquo;"
  );

  const handlePageClick = (url: string | null, page: number) => {
    if (!url) return;
    
    if (onPageChange) {
      onPageChange(page);
    }
  };

  // Function to get page number from a link label
  const getPageNumber = (label: string): number => {
    if (label === "&laquo; Previous") {
      return currentPage - 1;
    } else if (label === "Next &raquo;") {
      return currentPage + 1;
    } else {
      return parseInt(label, 10);
    }
  };

  return (
    <div className={cn("flex flex-col sm:flex-row items-center justify-between gap-3 py-4", className)}>
      <div className="text-sm text-neutral-600 dark:text-neutral-400">
        Showing <span className="font-medium">{from}</span> to{" "}
        <span className="font-medium">{to}</span> of{" "}
        <span className="font-medium">{total}</span> results
      </div>
      
      <div className="flex items-center gap-1">
        {pageLinks.map((link, index) => {
          // Extract page number or determine if it's a prev/next link
          const pageNumber = getPageNumber(link.label);
          const isDisabled = !link.url;
          
          // Determine if it's a prev or next link
          const isPrevLink = link.label === "&laquo; Previous";
          const isNextLink = link.label === "Next &raquo;";
          
          return (
            <Button
              key={index}
              variant={link.active ? "default" : "outline"}
              size="sm"
              className={cn(
                "h-8 w-8 p-0",
                isDisabled && "pointer-events-none opacity-50"
              )}
              disabled={isDisabled}
              onClick={() => handlePageClick(link.url, pageNumber)}
              asChild={!onPageChange && !isDisabled}
            >
              {!onPageChange && !isDisabled ? (
                <Link
                  href={link.url || '#'}
                  preserveScroll={preserveScroll}
                  preserveState={preserveState}
                  only={only.length > 0 ? only : undefined}
                >
                  {isPrevLink ? (
                    <ChevronLeft className="h-4 w-4" />
                  ) : isNextLink ? (
                    <ChevronRight className="h-4 w-4" />
                  ) : (
                    link.label
                  )}
                </Link>
              ) : (
                <span>
                  {isPrevLink ? (
                    <ChevronLeft className="h-4 w-4" />
                  ) : isNextLink ? (
                    <ChevronRight className="h-4 w-4" />
                  ) : (
                    link.label
                  )}
                </span>
              )}
            </Button>
          );
        })}
      </div>
    </div>
  );
}