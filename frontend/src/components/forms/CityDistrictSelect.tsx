'use client';

import { useEffect, useState } from 'react';
import { locationsApi, type CityOption, type DistrictOption } from '@/lib/api';

export interface CityDistrictValue {
    cityId?: number;
    cityName?: string;
    districtId?: number;
    districtName?: string;
}

interface Props {
    value: CityDistrictValue;
    onChange: (value: CityDistrictValue) => void;
    required?: boolean;
    /** İl seçimi kilitli — sadece ilçe seçilebilir (vergi no ile gelen retailer users için) */
    lockCity?: boolean;
}

export function CityDistrictSelect({ value, onChange, required = false, lockCity = false }: Props) {
    const [cities, setCities] = useState<CityOption[]>([]);
    const [districts, setDistricts] = useState<DistrictOption[]>([]);
    const [loadingCities, setLoadingCities] = useState(true);
    const [loadingDistricts, setLoadingDistricts] = useState(false);

    useEffect(() => {
        let active = true;
        setLoadingCities(true);
        locationsApi.getCities().then(res => {
            if (!active) return;
            if (res.data?.data) {
                setCities(res.data.data);
            }
            setLoadingCities(false);
        });
        return () => {
            active = false;
        };
    }, []);

    useEffect(() => {
        if (!value.cityId) {
            setDistricts([]);
            return;
        }
        let active = true;
        setLoadingDistricts(true);
        locationsApi.getDistricts(value.cityId).then(res => {
            if (!active) return;
            if (res.data?.data) {
                setDistricts(res.data.data);
            }
            setLoadingDistricts(false);
        });
        return () => {
            active = false;
        };
    }, [value.cityId]);

    return (
        <div className="grid grid-cols-2 gap-3">
            <div>
                <label className="block text-[11px] font-medium text-slate-600 mb-1">
                    İl{required ? ' *' : ''}
                </label>
                <select
                    value={value.cityId ?? ''}
                    onChange={e => {
                        const id = e.target.value ? parseInt(e.target.value, 10) : undefined;
                        const city = cities.find(c => c.id === id);
                        onChange({
                            cityId: id,
                            cityName: city?.name ?? '',
                            districtId: undefined,
                            districtName: '',
                        });
                    }}
                    disabled={loadingCities || lockCity}
                    className="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-sm focus:outline-none focus:border-slate-900 transition-colors disabled:opacity-60 disabled:bg-slate-50 disabled:cursor-not-allowed"
                >
                    <option value="">{loadingCities ? 'Yükleniyor...' : 'Seçiniz'}</option>
                    {cities.map(c => (
                        <option key={c.id} value={c.id}>{c.name}</option>
                    ))}
                </select>
            </div>
            <div>
                <label className="block text-[11px] font-medium text-slate-600 mb-1">İlçe</label>
                <select
                    value={value.districtId ?? ''}
                    onChange={e => {
                        const id = e.target.value ? parseInt(e.target.value, 10) : undefined;
                        const district = districts.find(d => d.id === id);
                        onChange({
                            ...value,
                            districtId: id,
                            districtName: district?.name ?? '',
                        });
                    }}
                    disabled={!value.cityId || loadingDistricts}
                    className="w-full h-10 px-3 rounded-lg border border-slate-300 bg-white text-sm focus:outline-none focus:border-slate-900 transition-colors disabled:opacity-50"
                >
                    <option value="">{!value.cityId ? 'Önce il seçin' : loadingDistricts ? 'Yükleniyor...' : 'Seçiniz'}</option>
                    {districts.map(d => (
                        <option key={d.id} value={d.id}>{d.name}</option>
                    ))}
                </select>
            </div>
        </div>
    );
}
