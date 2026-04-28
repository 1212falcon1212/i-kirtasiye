const svgRaw = `<svg xmlns="http://www.w3.org/2000/svg" width="240" height="240" opacity="0.08">
<g transform="translate(30,35) rotate(40)"><rect x="-8" y="-18" width="16" height="36" rx="8" fill="#94a3b8"/><rect x="-8" y="-18" width="16" height="18" rx="8" fill="#b0bec5"/></g>
<g transform="translate(150,50)"><rect x="-5" y="-16" width="10" height="32" rx="2.5" fill="#94a3b8"/><rect x="-16" y="-5" width="32" height="10" rx="2.5" fill="#94a3b8"/></g>
<g transform="translate(200,160)"><rect x="-12" y="-22" width="24" height="32" rx="3" fill="#94a3b8"/><rect x="-8" y="-28" width="16" height="8" rx="2" fill="#b0bec5"/><rect x="-6" y="-14" width="12" height="2.5" rx="1" fill="#b0bec5"/><rect x="-6" y="-8" width="12" height="2.5" rx="1" fill="#b0bec5"/></g>
<g transform="translate(75,145)"><circle cx="-7" cy="-12" r="3.5" fill="#94a3b8"/><circle cx="7" cy="-4" r="3.5" fill="#b0bec5"/><circle cx="-7" cy="4" r="3.5" fill="#94a3b8"/><circle cx="7" cy="12" r="3.5" fill="#b0bec5"/><line x1="-7" y1="-12" x2="7" y2="-4" stroke="#94a3b8" stroke-width="2"/><line x1="7" y1="-4" x2="-7" y2="4" stroke="#94a3b8" stroke-width="2"/><line x1="-7" y1="4" x2="7" y2="12" stroke="#94a3b8" stroke-width="2"/></g>
<g transform="translate(120,200)"><polyline points="-28,0 -16,0 -10,-12 0,14 8,-18 14,10 20,0 32,0" fill="none" stroke="#94a3b8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></g>
<g transform="translate(190,85)"><circle cx="0" cy="0" r="11" fill="none" stroke="#94a3b8" stroke-width="2.5"/><line x1="-11" y1="0" x2="11" y2="0" stroke="#b0bec5" stroke-width="2"/></g>
<g transform="translate(55,85) rotate(-30)"><ellipse cx="0" cy="0" rx="6" ry="12" fill="#94a3b8"/><ellipse cx="0" cy="-6" rx="6" ry="6" fill="#b0bec5"/></g>
<g transform="translate(32,200)"><path d="M-14,2 Q-16,-12 0,-16 Q16,-12 14,2 Z" fill="#94a3b8"/><line x1="0" y1="-16" x2="6" y2="-26" stroke="#b0bec5" stroke-width="3" stroke-linecap="round"/></g>
<g transform="translate(120,100)"><circle cx="0" cy="10" r="8" fill="none" stroke="#94a3b8" stroke-width="2.5"/><circle cx="0" cy="10" r="3" fill="#94a3b8"/><path d="M-4,2 Q-4,-12 -10,-18" fill="none" stroke="#94a3b8" stroke-width="2.5" stroke-linecap="round"/><path d="M4,2 Q4,-12 10,-18" fill="none" stroke="#94a3b8" stroke-width="2.5" stroke-linecap="round"/></g>
<g transform="translate(215,30) rotate(25)"><rect x="-4" y="-16" width="8" height="22" rx="1.5" fill="#94a3b8"/><ellipse cx="0" cy="-18" rx="6" ry="5" fill="#b0bec5"/><path d="M-3,6 L0,12 L3,6" fill="#94a3b8"/></g>
<g transform="translate(85,30)"><path d="M0,-14 Q12,-8 10,4 Q6,10 0,8 Q-6,10 -10,4 Q-12,-8 0,-14 Z" fill="#94a3b8"/><line x1="0" y1="-12" x2="0" y2="8" stroke="#b0bec5" stroke-width="1.5"/></g>
</svg>`;

function encodeSvg(svg: string): string {
    return svg
        .replace(/\n/g, ' ')
        .replace(/\s+/g, ' ')
        .replace(/#/g, '%23')
        .replace(/</g, '%3C')
        .replace(/>/g, '%3E')
        .replace(/"/g, "'");
}

export const medicalBgImage = `url("data:image/svg+xml,${encodeSvg(svgRaw)}")`;
