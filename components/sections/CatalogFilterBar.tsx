import React, { useMemo, useState } from 'react'
import { ChevronDown, Filter, Search, ArrowUpDown, X, ThumbsUp, Star, CircleDollarSign, CalendarDays } from "lucide-react"
import { UI_CONFIG } from "@/lib/config"

type FiltersState = {
  category: string[];
  priceRange: string[];
  pandit: string[];
  ritualType: string[];
  availability: string[];
};

interface CatalogFilterBarProps {
  selectedFilters: FiltersState;
  showOnSale: boolean;
  setShowFilters: (val: boolean) => void;
  sortBy: string;
  setSortBy: (val: string) => void;
  searchQuery: string;
  setSearchQuery: (val: string) => void;
  totalResults: number;
}

const SORT_OPTIONS = [
  { value: 'popularity', label: 'Recommended', description: 'Best match for you', icon: ThumbsUp },
  { value: 'rating', label: 'Highest Rated', description: 'Top rated by customers', icon: Star },
  { value: 'price', label: 'Price: Low to High', description: 'Affordable first', icon: CircleDollarSign },
  { value: 'price-desc', label: 'Price: High to Low', description: 'Premium first', icon: CircleDollarSign },
  { value: 'date', label: 'Newest', description: 'Recently added', icon: CalendarDays },
]

export function CatalogFilterBar({
  selectedFilters,
  showOnSale,
  setShowFilters,
  sortBy,
  setSortBy,
  searchQuery,
  setSearchQuery,
  totalResults,
}: CatalogFilterBarProps) {
  
  const [showSortModal, setShowSortModal] = useState(false);
  const [tempSort, setTempSort] = useState(sortBy);

  const hasActiveFilters = useMemo(() => {
    return (
      selectedFilters.category.length > 0 ||
      selectedFilters.priceRange.length > 0 ||
      selectedFilters.pandit.length > 0 ||
      selectedFilters.ritualType.length > 0 ||
      selectedFilters.availability.length > 0 ||
      showOnSale
    )
  }, [selectedFilters, showOnSale])

  const activeFilterCount = selectedFilters.category.length + selectedFilters.priceRange.length + selectedFilters.pandit.length + selectedFilters.ritualType.length + selectedFilters.availability.length + (showOnSale ? 1 : 0);

  const handleApplySort = () => {
    setSortBy(tempSort);
    setShowSortModal(false);
  }

  const handleOpenSort = () => {
    setTempSort(sortBy);
    setShowSortModal(true);
  }

  return (
    <>
      {/* Mobile Sticky Filter & Sort Bar */}
      <div className="sticky top-0 z-20 border-b border-[#e8e4db] bg-white/95 backdrop-blur-md md:hidden px-4 py-3">
        <div className="flex items-center justify-between">
          
          {/* Dynamic Total */}
          <div className="text-[14px] font-extrabold text-[#10263f]">
            {totalResults} {totalResults === 1 ? UI_CONFIG.naming.itemSingular : UI_CONFIG.naming.itemPlural}
          </div>

          <div className="flex items-center gap-2.5">
            {/* Filters Pill */}
            <button
              onClick={(event) => {
                event.stopPropagation()
                setShowFilters(true)
              }}
              className={`flex items-center justify-center gap-2 rounded-full border px-6 py-2.5 text-[15px] font-bold transition-all active:scale-95 ${
                hasActiveFilters
                  ? 'border-[#0c5647] bg-[#edf4f1] text-[#0c5647]'
                  : 'border-[#dde3e0] bg-white text-[#465565] active:bg-gray-50'
              }`}
            >
              <Filter className="h-[18px] w-[18px]" strokeWidth={2.5} />
              <span>Filter</span>
              {hasActiveFilters && (
                <span className="flex h-[22px] min-w-[22px] items-center justify-center rounded-full bg-[#0c5647] text-[12px] font-extrabold text-white ml-1">
                  {activeFilterCount}
                </span>
              )}
            </button>

            {/* Sort Pill */}
            <button 
              onClick={handleOpenSort}
              className={`flex items-center justify-center gap-2 rounded-full border px-6 py-2.5 text-[15px] font-bold transition-all active:scale-95 ${
                sortBy !== 'popularity' && sortBy !== 'price-desc' 
                  ? 'border-[#0c5647] bg-[#edf4f1] text-[#0c5647]' 
                  : 'border-[#dde3e0] bg-white text-[#465565] active:bg-gray-50'
              }`}
            >
              <ArrowUpDown className="h-[18px] w-[18px]" strokeWidth={2.5} />
              <span>Sort</span>
            </button>
          </div>
        </div>
      </div>

      {/* MOBILE SORT BOTTOM SHEET */}
      {showSortModal && (
        <div className="fixed inset-0 z-50 flex flex-col justify-end md:hidden">
          {/* Backdrop */}
          <div 
            className="absolute inset-0 bg-black/50 animate-fade-in backdrop-blur-[2px]" 
            onClick={() => setShowSortModal(false)} 
          />

          {/* Sheet Content */}
          <div className="relative z-10 flex h-[75vh] w-full flex-col overflow-hidden rounded-t-[20px] bg-white shadow-2xl animate-slide-up">
            
            {/* Drag Handle */}
            <div className="absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300 z-30"></div>

            {/* Header */}
            <div className="flex items-center justify-between border-b border-[#f0f0f0] px-5 pt-6 pb-4 bg-white relative z-20">
              <h2 className="text-[18px] font-extrabold text-[#10263f]">
                Sort By
              </h2>
              <button onClick={() => setShowSortModal(false)} className="text-gray-400 hover:bg-gray-100 rounded-full p-1 transition-colors active:scale-95">
                <X className="h-6 w-6" strokeWidth={2} />
              </button>
            </div>

            {/* Options */}
            <div className="flex-1 overflow-y-auto px-5 py-4 pb-28 bg-white hide-scrollbar">
              <div className="space-y-6">
                {SORT_OPTIONS.map((option) => {
                  const Icon = option.icon;
                  const isSelected = tempSort === option.value;
                  return (
                    <label 
                      key={option.value} 
                      className="flex items-center justify-between cursor-pointer group select-none"
                    >
                      <div className="flex items-center gap-4">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-[#f8faf9] group-hover:bg-[#edf4f1] transition-colors">
                          <Icon className={`h-5 w-5 ${isSelected ? 'text-[#0c5647]' : 'text-[#526271]'}`} strokeWidth={2} />
                        </div>
                        <div className="flex flex-col">
                          <span className={`text-[15px] font-bold ${isSelected ? 'text-[#0c5647]' : 'text-[#10263f]'} transition-colors`}>
                            {option.label}
                          </span>
                          <span className="text-[13px] font-medium text-gray-500">
                            {option.description}
                          </span>
                        </div>
                      </div>
                      
                      {/* Radio Check */}
                      <div className="relative flex h-6 w-6 items-center justify-center">
                        <input
                          type="radio"
                          name="sort-mobile"
                          value={option.value}
                          checked={isSelected}
                          onChange={(e) => setTempSort(e.target.value)}
                          className="peer absolute h-full w-full opacity-0 cursor-pointer"
                        />
                        <div className={`h-[22px] w-[22px] rounded-full border-[2px] transition-colors ${isSelected ? 'border-[#0c5647]' : 'border-gray-300 peer-hover:border-[#0c5647]'}`}></div>
                        {isSelected && <div className="absolute h-[12px] w-[12px] rounded-full bg-[#0c5647]"></div>}
                      </div>
                    </label>
                  )
                })}
              </div>
            </div>

            {/* Footer */}
            <div className="absolute bottom-0 left-0 right-0 p-5 bg-white border-t border-[#e8e4db] shadow-[0_-4px_15px_rgba(0,0,0,0.03)] z-20">
              <button 
                onClick={handleApplySort}
                className="w-full rounded-xl bg-[#0c5647] py-3.5 text-[15px] font-bold text-white hover:bg-[#094135] active:scale-[0.98] transition-all shadow-lg shadow-[#0c5647]/20"
              >
                Apply
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Desktop Filter Bar */}
      <section className="hidden md:block mx-auto max-w-7xl px-4 sm:px-6 mt-8 mb-4">
        <div className="flex items-center justify-between border-b border-[#e8e4db] pb-4">
          
          <div className="flex items-center gap-6">
            {/* Filters Button */}
            <button 
              onClick={() => setShowFilters(true)} 
              className={`flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-bold transition-all duration-200 ${
                hasActiveFilters 
                  ? 'border-[#0c5647] bg-[#0c5647] text-white' 
                  : 'border-[#e8e4db] bg-white text-[#10263f] hover:border-[#d6d2c4] hover:bg-gray-50'
              }`}
            >
              <Filter className="h-4 w-4" />
              <span>Filters</span>
              {hasActiveFilters && (
                <span className="flex h-[20px] min-w-[20px] items-center justify-center rounded-full bg-[#f2cd71] px-1.5 text-[10px] font-extrabold text-[#10263f]">
                  {activeFilterCount}
                </span>
              )}
            </button>
          </div>

          {/* Right Side: Search & Sort By */}
          <div className="flex items-center gap-4 relative">
            
            {/* Realtime Search Bar */}
            <div className="relative hidden md:flex items-center">
              <Search className="absolute left-3 h-4 w-4 text-gray-400" />
              <input
                type="text"
                placeholder={UI_CONFIG.naming.searchPlaceholder}
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="h-[38px] w-[200px] lg:w-[280px] rounded-lg border border-[#e8e4db] pl-9 pr-4 text-sm font-medium outline-none placeholder:text-gray-400 focus:border-[#0c5647] focus:ring-1 focus:ring-[#0c5647]/10 transition-all bg-[#fcfcfb]"
              />
            </div>

            {/* Sort By */}
            <div className="flex items-center gap-3 relative pl-4 border-l border-[#e8e4db]">
            <span className="text-sm font-medium text-gray-500">Sort by:</span>
            <div className="flex items-center gap-1.5 cursor-pointer group">
              <span className="text-sm font-bold text-[#10263f] group-hover:text-[#0c5647] transition-colors">
                {sortBy === 'popularity' ? 'Recommended' : sortBy === 'price-desc' ? 'Price: High to Low' : sortBy === 'price' ? 'Price: Low to High' : sortBy === 'rating' ? 'Top Rated' : 'Newest'}
              </span>
              <ChevronDown className="h-4 w-4 text-[#10263f] group-hover:text-[#0c5647] transition-colors" />
            </div>
            <select
              value={sortBy}
              onChange={(event) => setSortBy(event.target.value)}
              className="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
            >
              <option value="popularity">Recommended</option>
              <option value="price-desc">Price: High to Low</option>
              <option value="price">Price: Low to High</option>
              <option value="rating">Top Rated</option>
              <option value="date">Newest</option>
            </select>
          </div>

          </div>
        </div>
      </section>
    </>
  )
}
