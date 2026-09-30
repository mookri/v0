"use client";

import { ShieldCheck, Lock, Award } from "lucide-react";

export default function TrustBadges() {
    return (
        <div className="mt-4 rounded-xl border border-neutral-200 bg-white p-4">
            <h3 className="mb-3 text-[11px] font-bold uppercase tracking-wider text-neutral-500 text-center border-b border-neutral-100 pb-2">
                Why Families Trust Navdhya
            </h3>
            
            <div className="flex items-start justify-between gap-2 pt-1">
                <div className="flex flex-1 flex-col items-center gap-1.5 text-center">
                    <ShieldCheck className="h-[18px] w-[18px] text-neutral-600" strokeWidth={1.5} />
                    <span className="text-[10px] font-medium leading-[1.1] text-neutral-600">
                        Verified<br />Pandits
                    </span>
                </div>
                
                <div className="flex flex-1 flex-col items-center gap-1.5 text-center">
                    <Lock className="h-[18px] w-[18px] text-neutral-600" strokeWidth={1.5} />
                    <span className="text-[10px] font-medium leading-[1.1] text-neutral-600">
                        Secure<br />Payments
                    </span>
                </div>
                
                <div className="flex flex-1 flex-col items-center gap-1.5 text-center">
                    <Award className="h-[18px] w-[18px] text-neutral-600" strokeWidth={1.5} />
                    <span className="text-[10px] font-medium leading-[1.1] text-neutral-600">
                        Vedic<br />Adherence
                    </span>
                </div>
            </div>
        </div>
    );
}
