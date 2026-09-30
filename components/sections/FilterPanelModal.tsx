import React, { useState } from 'react'
import { ArrowRight, Flower2, Grid, User, Calendar, IndianRupee, Search, X, ChevronUp, ChevronDown } from "lucide-react"
import { UI_CONFIG } from "@/lib/config"

type FiltersState = {
  category: string[];
  priceRange: string[];
  pandit: string[];
  ritualType: string[];
  availability: string[];
};

interface FilterPanelModalProps {
  filters: FiltersState;
  selectedFilters: FiltersState;
  toggleFilter: (filterType: keyof FiltersState, value: string) => void;
  showOnSale: boolean;
  setShowOnSale: (val: boolean) => void;
  clearAllFilters: () => void;
  setShowFilters: (val: boolean) => void;
  filteredPujasCount: number;
}

export function FilterPanelModal({
  filters,
  selectedFilters,
  toggleFilter,
  showOnSale,
  setShowOnSale,
  clearAllFilters,
  setShowFilters,
  filteredPujasCount,
}: FilterPanelModalProps) {
  const [activeTab, setActiveTab] = useState<keyof FiltersState>('ritualType')
  const [ritualSearch, setRitualSearch] = useState("")

  const activeFilterCount = selectedFilters.category.length + selectedFilters.priceRange.length + selectedFilters.pandit.length + selectedFilters.ritualType.length + selectedFilters.availability.length + (showOnSale ? 1 : 0);

  const [expandedSections, setExpandedSections] = useState<Record<string, boolean>>({
    ritualType: true,
    category: true,
    pandit: true,
    availability: true,
    priceRange: true,
  });

  const toggleSection = (section: string) => {
    setExpandedSections(prev => ({ ...prev, [section]: !prev[section] }));
  };

  const tabMap = [
    { id: 'ritualType', label: 'Ritual', icon: <Flower2 className="h-4 w-4" /> },
    { id: 'category', label: 'Category', icon: <Grid className="h-4 w-4" /> },
    { id: 'pandit', label: 'Pandit', icon: <User className="h-4 w-4" /> },
    { id: 'availability', label: 'Availability', icon: <Calendar className="h-4 w-4" /> },
    { id: 'priceRange', label: 'Price', icon: <IndianRupee className="h-4 w-4" /> },
  ] as const

  return (
    <>
      {/* MOBILE FILTER SHEET */}
      <div className="fixed inset-0 z-50 flex flex-col justify-end md:hidden">
        <style>{`
          @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
          }
          @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
          }
          .animate-slide-up {
            animation: slideUp 0.35s cubic-bezier(0.32, 0.72, 0, 1) forwards;
          }
          .animate-fade-in {
            animation: fadeIn 0.25s ease-out forwards;
          }
          .hide-scrollbar::-webkit-scrollbar {
            display: none;
          }
          .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
          }
        `}</style>
        
        {/* Backdrop */}
        <div 
          className="absolute inset-0 bg-black/50 animate-fade-in backdrop-blur-[2px]" 
          onClick={() => setShowFilters(false)} 
        />

        {/* Sheet Content */}
        <div className="relative z-10 flex h-[90vh] w-full flex-col overflow-hidden rounded-t-[20px] bg-white shadow-2xl animate-slide-up">
          
          {/* Drag Handle */}
          <div className="absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300 z-30"></div>

          {/* Header */}
          <div className="flex items-center justify-between border-b border-[#f0f0f0] px-5 pt-6 pb-4 bg-white relative z-20">
            <h2 className="text-[18px] font-extrabold text-[#10263f]">
              Filters {activeFilterCount > 0 && <span className="text-[#0c5647]">({activeFilterCount})</span>}
            </h2>
            <div className="flex items-center gap-4">
              {activeFilterCount > 0 && (
                <button onClick={clearAllFilters} className="text-[13.5px] font-bold text-[#0c5647] hover:opacity-70 transition-opacity active:scale-95">
                  Clear all
                </button>
              )}
              <button onClick={() => setShowFilters(false)} className="text-gray-400 hover:bg-gray-100 rounded-full p-1 transition-colors active:scale-95">
                <X className="h-6 w-6" strokeWidth={2} />
              </button>
            </div>
          </div>

          {/* Scrollable Filters */}
          <div className="flex-1 overflow-y-auto px-5 py-4 pb-28 bg-white hide-scrollbar">
            
            {/* Rituals */}
            <div className="border-b border-[#f0f0f0] pb-6 mb-6">
              <div 
                className="flex items-center justify-between cursor-pointer mb-3 select-none"
                onClick={() => toggleSection('ritualType')}
              >
                <h3 className="text-[16px] font-bold text-[#10263f]">{UI_CONFIG.naming.itemSingular}</h3>
                {expandedSections['ritualType'] ? <ChevronUp className="h-5 w-5 text-gray-400" /> : <ChevronDown className="h-5 w-5 text-gray-400" />}
              </div>
              
              {expandedSections['ritualType'] && (
                <div className="space-y-4 animate-fade-in">
                  {/* Selected Ritual Pills */}
                  {selectedFilters.ritualType.length > 0 && (
                    <div className="flex flex-wrap gap-2">
                      {selectedFilters.ritualType.map(val => (
                        <button
                          key={val}
                          onClick={() => toggleFilter("ritualType", val)}
                          className="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#edf4f1] border border-[#c0d6cf] text-[#0c5647] text-[12.5px] font-bold transition-all active:scale-95"
                        >
                          {val}
                          <X className="h-3 w-3" strokeWidth={2.5} />
                        </button>
                      ))}
                    </div>
                  )}

                  <div className="relative">
                    <Search className="absolute left-3.5 top-1/2 -translate-y-1/2 h-[18px] w-[18px] text-gray-400" />
                    <input 
                      type="text" 
                      placeholder={UI_CONFIG.naming.searchPlaceholder}
                      value={ritualSearch}
                      onChange={(e) => setRitualSearch(e.target.value)}
                      className="w-full h-11 pl-10 pr-10 rounded-xl border border-[#dde3e0] text-[14px] text-[#10263f] placeholder:text-gray-400 outline-none focus:border-[#0c5647] focus:ring-2 focus:ring-[#0c5647]/20 transition-all bg-[#fcfcfb]"
                    />
                    {ritualSearch && (
                      <button 
                        onClick={() => setRitualSearch('')}
                        className="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 p-0.5 rounded-full bg-gray-100"
                      >
                        <X className="h-3.5 w-3.5" />
                      </button>
                    )}
                  </div>
                  
                  <div className="space-y-4 pt-2">
                    {filters.ritualType
                      .filter(r => r.toLowerCase().includes(ritualSearch.toLowerCase()))
                      .slice(0, ritualSearch ? undefined : 5)
                      .map((value) => {
                        const checked = selectedFilters.ritualType.includes(value);
                        return (
                          <label key={value} className="flex items-center gap-3.5 cursor-pointer group select-none">
                            <div className={`flex items-center justify-center h-[20px] w-[20px] rounded-[5px] border-[1.5px] transition-colors ${checked ? 'bg-[#0c5647] border-[#0c5647]' : 'border-gray-300 bg-white group-hover:border-[#0c5647]'}`}>
                              {checked && (
                                <svg viewBox="0 0 24 24" fill="none" className="h-[12px] w-[12px] text-white">
                                  <path d="M5 12.5L9.2 17L19 7" stroke="currentColor" strokeWidth="3.5" strokeLinecap="round" strokeLinejoin="round"/>
                                </svg>
                              )}
                            </div>
                            <span className={`text-[14.5px] font-medium transition-colors ${checked ? 'text-[#10263f]' : 'text-[#465565] group-hover:text-[#10263f]'}`}>{value}</span>
                          </label>
                        )
                    })}
                    {!ritualSearch && filters.ritualType.length > 5 && (
                      <button className="text-[13.5px] font-bold text-[#0c5647] flex items-center gap-1 mt-2 active:opacity-70 transition-opacity">
                        Show more <ChevronDown className="h-4 w-4" />
                      </button>
                    )}
                  </div>
                </div>
              )}
            </div>

            {/* Category */}
            <div className="border-b border-[#f0f0f0] pb-6 mb-6">
              <div 
                className="flex items-center justify-between cursor-pointer mb-4 select-none"
                onClick={() => toggleSection('category')}
              >
                <h3 className="text-[16px] font-bold text-[#10263f]">Category</h3>
                {expandedSections['category'] ? <ChevronUp className="h-5 w-5 text-gray-400" /> : <ChevronDown className="h-5 w-5 text-gray-400" />}
              </div>
              
              {expandedSections['category'] && (
                <div className="flex flex-wrap gap-2.5 animate-fade-in">
                  {filters.category.map((value) => {
                    const checked = selectedFilters.category.includes(value);
                    return (
                      <button
                        key={value}
                        onClick={() => toggleFilter("category", value)}
                        className={`flex items-center gap-1.5 px-4 py-2.5 rounded-full text-[13.5px] font-semibold transition-all active:scale-[0.97] ${
                          checked 
                            ? 'bg-[#edf4f1] text-[#0c5647] border border-[#c0d6cf]' 
                            : 'bg-white text-[#465565] border border-[#dde3e0] hover:bg-gray-50 hover:border-gray-300'
                        }`}
                      >
                        {value}
                        {checked && <X className="h-3.5 w-3.5" strokeWidth={2.5} />}
                      </button>
                    )
                  })}
                </div>
              )}
            </div>

            {/* Pandit Type */}
            <div className="border-b border-[#f0f0f0] pb-6 mb-6">
              <div 
                className="flex items-center justify-between cursor-pointer mb-4 select-none"
                onClick={() => toggleSection('pandit')}
              >
                <h3 className="text-[16px] font-bold text-[#10263f]">Pandit</h3>
                {expandedSections['pandit'] ? <ChevronUp className="h-5 w-5 text-gray-400" /> : <ChevronDown className="h-5 w-5 text-gray-400" />}
              </div>
              
              {expandedSections['pandit'] && (
                <div className="space-y-4 animate-fade-in">
                  {filters.pandit.map((value) => {
                    const checked = selectedFilters.pandit.includes(value);
                    return (
                      <label key={value} className="flex items-center gap-3.5 cursor-pointer group select-none">
                        <div className={`flex items-center justify-center h-[20px] w-[20px] rounded-full border-[2px] transition-colors ${checked ? 'border-[#0c5647]' : 'border-gray-300 group-hover:border-[#0c5647]'}`}>
                          {checked && <div className="h-[10px] w-[10px] rounded-full bg-[#0c5647]" />}
                        </div>
                        <span className={`text-[14.5px] font-medium transition-colors ${checked ? 'text-[#10263f]' : 'text-[#465565] group-hover:text-[#10263f]'}`}>{value}</span>
                      </label>
                    )
                  })}
                  <button className="text-[13.5px] font-bold text-[#0c5647] flex items-center gap-1 mt-2 active:opacity-70 transition-opacity">
                    Show more <ChevronDown className="h-4 w-4" />
                  </button>
                </div>
              )}
            </div>

            {/* Availability */}
            <div className="border-b border-[#f0f0f0] pb-6 mb-6">
              <div 
                className="flex items-center justify-between cursor-pointer mb-4 select-none"
                onClick={() => toggleSection('availability')}
              >
                <h3 className="text-[16px] font-bold text-[#10263f]">Availability</h3>
                {expandedSections['availability'] ? <ChevronUp className="h-5 w-5 text-gray-400" /> : <ChevronDown className="h-5 w-5 text-gray-400" />}
              </div>
              
              {expandedSections['availability'] && (
                <div className="space-y-4 animate-fade-in">
                  {/* Selected Pill */}
                  {selectedFilters.availability.length > 0 && (
                    <div className="flex flex-wrap gap-2">
                      {selectedFilters.availability.map(val => (
                        <button
                          key={val}
                          onClick={() => toggleFilter("availability", val)}
                          className="flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-[#edf4f1] border border-[#c0d6cf] text-[#0c5647] text-[12.5px] font-bold transition-all active:scale-95"
                        >
                          {val}
                          <X className="h-3 w-3" strokeWidth={2.5} />
                        </button>
                      ))}
                    </div>
                  )}

                  <div className="grid grid-cols-2 gap-4">
                    {filters.availability.map((value) => {
                      const checked = selectedFilters.availability.includes(value);
                      return (
                        <label key={value} className="flex items-center gap-3.5 cursor-pointer col-span-2 sm:col-span-1 group select-none">
                          <div className={`flex items-center justify-center h-[20px] w-[20px] rounded-full border-[2px] transition-colors ${checked ? 'border-[#0c5647]' : 'border-gray-300 group-hover:border-[#0c5647]'}`}>
                            {checked && <div className="h-[10px] w-[10px] rounded-full bg-[#0c5647]" />}
                          </div>
                          <span className={`text-[14.5px] font-medium transition-colors ${checked ? 'text-[#10263f]' : 'text-[#465565] group-hover:text-[#10263f]'}`}>{value}</span>
                        </label>
                      )
                    })}
                    
                    {/* Custom Date fake input */}
                    <label className="flex items-center gap-3.5 cursor-pointer col-span-2 sm:col-span-1 group select-none">
                      <div className="flex h-[20px] w-[20px] items-center justify-center rounded-full border-[2px] border-gray-300 text-gray-400 transition-colors group-hover:border-[#0c5647] group-hover:text-[#0c5647]">
                        <Calendar className="h-3 w-3" />
                      </div>
                      <span className="text-[14.5px] font-medium text-[#465565] group-hover:text-[#10263f] transition-colors">Custom date</span>
                    </label>
                  </div>
                </div>
              )}
            </div>
            
            {/* Price Range */}
            <div className="pb-4">
              <div 
                className="flex items-center justify-between cursor-pointer mb-4 select-none"
                onClick={() => toggleSection('priceRange')}
              >
                <h3 className="text-[16px] font-bold text-[#10263f]">Price Range</h3>
                {expandedSections['priceRange'] ? <ChevronUp className="h-5 w-5 text-gray-400" /> : <ChevronDown className="h-5 w-5 text-gray-400" />}
              </div>
              
              {expandedSections['priceRange'] && (
                <div className="space-y-6 animate-fade-in">
                  <span className="text-[14.5px] font-medium text-[#465565]">Any price</span>
                  
                  {/* UX Polished Slider visual */}
                  <div className="relative px-2 py-5">
                    <div className="absolute top-1/2 left-2 right-2 h-[6px] -translate-y-1/2 bg-[#0c5647] rounded-full"></div>
                    <div className="absolute top-1/2 left-2 h-[22px] w-[22px] -translate-y-1/2 -translate-x-1/2 bg-white border-[3px] border-[#0c5647] rounded-full shadow-md cursor-grab active:cursor-grabbing hover:scale-110 transition-transform"></div>
                    <div className="absolute top-1/2 right-2 h-[22px] w-[22px] -translate-y-1/2 translate-x-1/2 bg-white border-[3px] border-[#0c5647] rounded-full shadow-md cursor-grab active:cursor-grabbing hover:scale-110 transition-transform"></div>
                    <div className="flex justify-between mt-6 text-[12.5px] text-gray-500 font-semibold px-0.5">
                      <span>₹0</span>
                      <span>₹50,000</span>
                      <span>₹1L+</span>
                    </div>
                  </div>

                  <div className="flex flex-wrap gap-2.5">
                    {filters.priceRange.map((value) => {
                      const checked = selectedFilters.priceRange.includes(value);
                      return (
                        <button
                          key={value}
                          onClick={() => toggleFilter("priceRange", value)}
                          className={`px-3.5 py-2 rounded-full text-[13.5px] font-semibold transition-all active:scale-[0.97] ${
                            checked 
                              ? 'bg-[#edf4f1] text-[#0c5647] border border-[#c0d6cf]' 
                              : 'bg-white text-[#465565] border border-[#dde3e0] hover:bg-gray-50 hover:border-gray-300'
                          }`}
                        >
                          {value}
                        </button>
                      )
                    })}
                  </div>
                </div>
              )}
            </div>

          </div>

          {/* Footer Actions */}
          <div className="absolute bottom-0 left-0 right-0 flex items-center justify-between border-t border-[#e8e4db] bg-white px-5 py-4 z-20 shadow-[0_-4px_15px_rgba(0,0,0,0.03)]">
            <button 
              onClick={clearAllFilters} 
              className={`text-[15px] font-bold transition-opacity active:scale-95 ${activeFilterCount > 0 ? 'text-[#0c5647] hover:opacity-70' : 'text-gray-400 opacity-60 cursor-not-allowed'}`}
              disabled={activeFilterCount === 0}
            >
              Clear all
            </button>
            <button onClick={() => setShowFilters(false)} className="flex items-center justify-center min-w-[200px] gap-2 rounded-xl bg-[#0c5647] px-5 py-3.5 text-[15px] font-bold text-white hover:bg-[#094135] transition-all active:scale-[0.98] shadow-lg shadow-[#0c5647]/20">
              Show {filteredPujasCount} {filteredPujasCount === 1 ? UI_CONFIG.naming.itemSingular : UI_CONFIG.naming.itemPlural}
              <ArrowRight className="h-[18px] w-[18px]" strokeWidth={2.5} />
            </button>
          </div>
        </div>
      </div>

      {/* DESKTOP FILTER MODAL */}
      {/* DESKTOP FILTER MODAL */}
      <div className="fixed inset-0 z-50 hidden md:flex items-center justify-center bg-black/30 px-4">

        <div
          className="
      flex
      h-[386px]
      w-full
      max-w-[936px]
      overflow-hidden
      rounded-[10px]
      border
      border-[#E5E9E7]
      bg-white
      shadow-[0_12px_40px_rgba(16,38,63,0.10)]
    "
        >

          {/* =========================
        LEFT SIDEBAR
    ========================= */}
          <aside
            className="
        flex
        w-[165px]
        shrink-0
        flex-col
        border-r
        border-[#E6EBE8]
        bg-[#F8FAF9]
        pt-[17px]
      "
          >

            {/* Sidebar title */}
            <h2
              className="
          mb-[11px]
          px-[17px]
          text-[13px]
          font-bold
          leading-[18px]
          text-[#10263F]
        "
            >
              Filters
            </h2>

            {/* Navigation */}
            <div className="flex flex-col">

              {tabMap.map((tab) => {
                const active = activeTab === tab.id

                return (
                  <button
                    key={tab.id}
                    type="button"
                    onClick={() =>
                      setActiveTab(tab.id as keyof FiltersState)
                    }
                    className={`
                relative
                flex
                h-[31px]
                w-full
                items-center
                gap-[9px]
                px-[17px]
                text-left
                text-[11px]
                font-medium
                transition-colors
                ${active
                        ? "bg-[#EDF4F1] text-[#0C5647]"
                        : "text-[#405060] hover:bg-[#F1F5F3]"
                      }
              `}
                  >

                    {/* Active indicator */}
                    {active && (
                      <span
                        className="
                    absolute
                    left-0
                    top-0
                    h-full
                    w-[3px]
                    rounded-r-full
                    bg-[#0C5647]
                  "
                      />
                    )}

                    <span
                      className={`
                  flex
                  shrink-0
                  items-center
                  justify-center
                  ${active
                          ? "text-[#0C5647]"
                          : "text-[#526271]"
                        }
                `}
                    >
                      {React.cloneElement(tab.icon, {
                        className: "h-[14px] w-[14px]",
                      })}
                    </span>

                    <span>{tab.label}</span>

                  </button>
                )
              })}

            </div>
          </aside>


          {/* =========================
        RIGHT CONTENT
    ========================= */}
          <section className="flex min-w-0 flex-1 flex-col bg-white">

            {/* =========================
          HEADER
      ========================= */}
            <div
              className="
          flex
          h-[58px]
          shrink-0
          items-center
          justify-between
          border-b
          border-[#EEF1EF]
          px-[24px]
        "
            >

              <h3
                className="
            text-[13px]
            font-bold
            leading-none
            text-[#10263F]
          "
              >
                Select {tabMap.find(
                  (t) => t.id === activeTab
                )?.label}
              </h3>


              <div className="flex items-center gap-[23px]">

                {/* Clear */}
                <button
                  type="button"
                  onClick={clearAllFilters}
                  className="
              text-[11px]
              font-bold
              text-[#0C5647]
              transition-opacity
              hover:opacity-70
            "
                >
                  Clear
                </button>

                {/* Close */}
                <button
                  type="button"
                  aria-label="Close filters"
                  onClick={() => setShowFilters(false)}
                  className="
              flex
              h-[24px]
              w-[24px]
              items-center
              justify-center
              rounded-full
              text-[#19344B]
              transition-colors
              hover:bg-[#F3F5F4]
            "
                >
                  <X className="h-[18px] w-[18px] stroke-[1.8]" />
                </button>

              </div>

            </div>


            {/* =========================
          SEARCH
      ========================= */}
            {activeTab === "ritualType" && (
              <div className="shrink-0 px-[24px] pt-[10px]">

                <div className="relative">

                  <Search
                    className="
                absolute
                left-[11px]
                top-1/2
                h-[14px]
                w-[14px]
                -translate-y-1/2
                text-[#87939D]
              "
                  />

                  <input
                    type="text"
                    placeholder="Search rituals..."
                    className="
                h-[29px]
                w-full
                rounded-full
                border
                border-[#DDE3E0]
                bg-white
                pl-[32px]
                pr-[12px]
                text-[11px]
                font-medium
                text-[#10263F]
                outline-none
                placeholder:text-[#9AA3AA]
                focus:border-[#0C5647]
                focus:ring-1
                focus:ring-[#0C5647]/10
              "
                  />

                </div>

              </div>
            )}


            {/* =========================
          OPTIONS
      ========================= */}
            <div
              className="
          min-h-0
          flex-1
          overflow-y-auto
          px-[24px]
          pt-[9px]
        "
            >

              <div
                className="
            grid
            grid-cols-3
            gap-x-[30px]
            gap-y-[7px]
          "
              >

                {filters[activeTab]?.map((value) => {

                  const checked =
                    selectedFilters[activeTab].includes(value)

                  return (
                    <label
                      key={value}
                      className="
                  flex
                  min-h-[23px]
                  cursor-pointer
                  items-center
                  gap-[8px]
                  text-[11px]
                  font-medium
                  text-[#465565]
                  select-none
                "
                    >

                      {/* Checkbox */}
                      <span className="relative flex h-[15px] w-[15px] shrink-0">

                        <input
                          type="checkbox"
                          checked={checked}
                          onChange={() =>
                            toggleFilter(activeTab, value)
                          }
                          className="
                      peer
                      absolute
                      inset-0
                      z-10
                      h-full
                      w-full
                      cursor-pointer
                      appearance-none
                      rounded-[3px]
                      border
                      border-[#CBD3D0]
                      bg-white
                      checked:border-[#0C5647]
                      checked:bg-[#0C5647]
                    "
                        />

                        {/* Check */}
                        <svg
                          viewBox="0 0 24 24"
                          fill="none"
                          className="
                      pointer-events-none
                      absolute
                      left-[2px]
                      top-[2px]
                      z-20
                      h-[11px]
                      w-[11px]
                      text-white
                      opacity-0
                      peer-checked:opacity-100
                    "
                        >
                          <path
                            d="M5 12.5L9.2 17L19 7"
                            stroke="currentColor"
                            strokeWidth="3"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                          />
                        </svg>

                      </span>

                      <span className="leading-[16px]">
                        {value}
                      </span>

                    </label>
                  )
                })}

              </div>

            </div>


            {/* =========================
          FOOTER
      ========================= */}
            <div
              className="
          flex
          h-[55px]
          shrink-0
          items-center
          justify-end
          gap-[9px]
          border-t
          border-[#EEF1EF]
          px-[24px]
        "
            >

              {/* Cancel */}
              <button
                type="button"
                onClick={() => setShowFilters(false)}
                className="
            h-[33px]
            rounded-[9px]
            border
            border-[#DDE3E0]
            bg-white
            px-[19px]
            text-[11px]
            font-bold
            text-[#26394A]
            transition-colors
            hover:bg-[#F7F9F8]
          "
              >
                Cancel
              </button>


              {/* Show Results */}
              <button
                type="button"
                onClick={() => setShowFilters(false)}
                className="
            flex
            h-[33px]
            items-center
            gap-[7px]
            rounded-[9px]
            bg-[#0C5647]
            px-[15px]
            text-[11px]
            font-bold
            text-white
            transition-colors
            hover:bg-[#094135]
          "
              >

                <span>
                  Show {filteredPujasCount} Rituals
                </span>

                <ArrowRight
                  className="
              h-[14px]
              w-[14px]
              stroke-[2]
            "
                />

              </button>

            </div>

          </section>

        </div>
      </div>
    </>
  )
}
