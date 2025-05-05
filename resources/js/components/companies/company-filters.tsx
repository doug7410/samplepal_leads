import React, { useState, useCallback, memo } from 'react';
import { router } from '@inertiajs/react';
import { Search, X, MapPin, Map } from 'lucide-react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { DebouncedInput } from '@/components/ui/debounced-input';
import { DebouncedSelect } from '@/components/ui/debounced-select';

interface CompanyFiltersProps {
  initialFilters: {
    search: string;
    city: string;
    state: string;
  };
  filterOptions: {
    cities: string[];
    states: string[];
  };
}

// Function to convert text to title case
function toTitleCase(text: string): string {
  return text
    .toLowerCase()
    .split(' ')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ');
}

function CompanyFiltersComponent({ initialFilters, filterOptions }: CompanyFiltersProps) {
  const [filters, setFilters] = useState({
    search: initialFilters.search || '',
    city: initialFilters.city || 'all',
    state: initialFilters.state || 'all',
  });

  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSearchChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    setFilters(prev => ({ ...prev, search: e.target.value }));
  }, []);

  const handleCityChange = useCallback((value: string) => {
    setFilters(prev => ({ ...prev, city: value }));
  }, []);

  const handleStateChange = useCallback((value: string) => {
    setFilters(prev => ({ ...prev, state: value }));
  }, []);

  const clearSearch = useCallback(() => {
    setFilters(prev => ({ ...prev, search: '' }));
  }, []);

  const handleKeyDown = useCallback((e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Enter') {
      applyFilters();
    }
  }, []);

  const applyFilters = useCallback(() => {
    setIsSubmitting(true);

    router.get(route('companies.index'), {
      search: filters.search,
      city: filters.city,
      state: filters.state,
    }, {
      preserveState: true,
      preserveScroll: true,
      onFinish: () => setIsSubmitting(false),
    });
  }, [filters]);

  const resetFilters = useCallback(() => {
    setIsSubmitting(true);
    setFilters({
      search: '',
      city: 'all',
      state: 'all',
    });

    router.visit(route('companies.index'), {
      replace: true,
      preserveState: true,
      preserveScroll: true,
      only: ['companies'],
      onFinish: () => setIsSubmitting(false),
    });
  }, []);

  const hasActiveFilters = filters.search ||
    (filters.city && filters.city !== 'all') ||
    (filters.state && filters.state !== 'all');

  return (
    <Card className="p-4 mb-4">
      <div className="flex flex-col space-y-4 max-w-[700px]">
        <div className="flex items-center gap-2">
          <Search size={16} className="text-neutral-500" />
          <h2 className="text-sm font-medium">Filter Companies</h2>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
          <div className="relative flex-1">
            <div className="flex items-center gap-2 mb-2">
              <Search size={14} className="text-neutral-500" />
              <span className="text-sm font-medium">Company Name</span>
            </div>
            <div className="relative">
              <DebouncedInput
                type="text"
                placeholder="Search by company name..."
                value={filters.search}
                onChange={(value) => setFilters(prev => ({ ...prev, search: value }))}
                onKeyDown={handleKeyDown}
                className="pr-8"
                debounceTime={500}
              />
              {filters.search && (
                <button
                  type="button"
                  onClick={clearSearch}
                  className="absolute right-2 top-1/2 -translate-y-1/2 text-neutral-400 hover:text-neutral-600"
                >
                  <X size={16} />
                </button>
              )}
            </div>
          </div>

          <div className="flex-1">
            <div className="flex items-center gap-2 mb-2">
              <MapPin size={14} className="text-neutral-500" />
              <span className="text-sm font-medium">City/Region</span>
            </div>
            <DebouncedSelect
              value={filters.city}
              onValueChange={handleCityChange}
              placeholder="Select city"
              options={[
                { value: 'all', label: 'All Cities' },
                ...filterOptions.cities.map(city => ({
                  value: city || "empty",
                  label: toTitleCase(city)
                }))
              ]}
              debounceTime={300}
            />
          </div>

          <div className="flex-1">
            <div className="flex items-center gap-2 mb-2">
              <Map size={14} className="text-neutral-500" />
              <span className="text-sm font-medium">State</span>
            </div>
            <DebouncedSelect
              value={filters.state}
              onValueChange={handleStateChange}
              placeholder="Select state"
              options={[
                { value: 'all', label: 'All States' },
                ...filterOptions.states.map(state => ({
                  value: state || "empty",
                  label: state
                }))
              ]}
              debounceTime={300}
            />
          </div>
        </div>

        <div className="flex items-center gap-3 mt-2">
          <Button
            onClick={applyFilters}
            disabled={isSubmitting}
            size="sm"
          >
            Apply Filters
          </Button>
          {hasActiveFilters && (
            <Button
              onClick={resetFilters}
              disabled={isSubmitting}
              size="sm"
              variant="outline"
            >
              Reset All
            </Button>
          )}
        </div>
      </div>
    </Card>
  );
}

// Memoize the component to prevent unnecessary re-renders
export const CompanyFilters = memo(CompanyFiltersComponent);
