<svg
    {{ $attributes->merge(['class' => 'w-full h-full pointer-events-none stroke-current fill-current']) }}
    viewBox="0 0 1200 600"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    aria-hidden="true"
    style="opacity: 0.05;"
>
    <defs>
        <pattern id="ct-grid" width="40" height="40" patternUnits="userSpaceOnUse">
            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="0.5" stroke-dasharray="2,4" />
        </pattern>
    </defs>

    {{-- Subtle global coordinate grid lines --}}
    <rect width="1200" height="600" fill="url(#ct-grid)" />

    {{-- Faint Oceanic Trade Lane Arcs (Great Circle Bezier Curves) --}}
    <g fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-dasharray="4,6">
        <!-- Trans-Pacific: Shanghai (860, 230) -> Los Angeles (240, 215) -->
        <path d="M 860 230 Q 1050 110 1200 170" />
        <path d="M 0 170 Q 120 120 240 215" />

        <!-- Asia-Europe: Shanghai (860, 230) -> Singapore (820, 320) -> Suez (650, 235) -> Rotterdam (570, 165) -->
        <path d="M 860 230 Q 840 280 820 320" />
        <path d="M 820 320 Q 720 310 650 235" />
        <path d="M 650 235 Q 600 185 570 165" />

        <!-- Trans-Atlantic: Rotterdam (570, 165) -> New York (325, 205) -->
        <path d="M 570 165 Q 440 140 325 205" />

        <!-- Panama Canal Route: New York (325, 205) -> Panama (310, 310) -> Los Angeles (240, 215) -->
        <path d="M 325 205 Q 330 270 310 310" />
        <path d="M 310 310 Q 260 270 240 215" />

        <!-- Cape Route: Singapore (820, 320) -> Cape Town (620, 470) -> Rotterdam (570, 165) -->
        <path d="M 820 320 Q 720 420 620 470" />
        <path d="M 620 470 Q 550 340 570 165" />

        <!-- Australasia: Singapore (820, 320) -> Sydney (950, 460) -->
        <path d="M 820 320 Q 900 380 950 460" />

        <!-- South America East: Panama (310, 310) -> Santos (390, 440) -->
        <path d="M 310 310 Q 370 360 390 440" />
    </g>

    {{-- Major Ocean Seaports (UN/LOCODE Hubs) as Anchor Rings --}}
    <g fill="currentColor" stroke="currentColor">
        <!-- Shanghai (CNSHA) -->
        <circle cx="860" cy="230" r="3" />
        <circle cx="860" cy="230" r="7" fill="none" stroke-width="0.8" />

        <!-- Singapore (SGSIN) -->
        <circle cx="820" cy="320" r="3.5" />
        <circle cx="820" cy="320" r="8" fill="none" stroke-width="0.8" />

        <!-- Rotterdam (NLRTM) -->
        <circle cx="570" cy="165" r="3" />
        <circle cx="570" cy="165" r="7" fill="none" stroke-width="0.8" />

        <!-- Los Angeles (USLAX) -->
        <circle cx="240" cy="215" r="3" />
        <circle cx="240" cy="215" r="7" fill="none" stroke-width="0.8" />

        <!-- New York (USNYC) -->
        <circle cx="325" cy="205" r="3" />
        <circle cx="325" cy="205" r="7" fill="none" stroke-width="0.8" />

        <!-- Dubai / Jebel Ali (AEJEA) -->
        <circle cx="700" cy="250" r="2.5" />
        <!-- Tanjung Priok (IDJKT) -->
        <circle cx="835" cy="345" r="2.5" />
        <!-- Hamburg (DEHAM) -->
        <circle cx="580" cy="155" r="2.5" />
        <!-- Busan (KRPUS) -->
        <circle cx="890" cy="225" r="2.5" />
        <!-- Santos (BRSSZ) -->
        <circle cx="390" cy="440" r="2.5" />
        <!-- Cape Town (ZACPT) -->
        <circle cx="620" cy="470" r="2.5" />
        <!-- Sydney (AUMEL) -->
        <circle cx="950" cy="460" r="2.5" />
    </g>

    {{-- Dense dot clusters representing continental landmass geometry --}}
    <g fill="currentColor">
        <!-- North America -->
        <circle cx="220" cy="160" r="1.5"/><circle cx="240" cy="150" r="1.5"/><circle cx="260" cy="155" r="1.5"/><circle cx="280" cy="170" r="1.5"/><circle cx="300" cy="180" r="1.5"/>
        <circle cx="210" cy="180" r="1.5"/><circle cx="230" cy="190" r="1.5"/><circle cx="250" cy="200" r="1.5"/><circle cx="270" cy="210" r="1.5"/><circle cx="290" cy="220" r="1.5"/><circle cx="310" cy="210" r="1.5"/>
        <circle cx="220" cy="220" r="1.5"/><circle cx="240" cy="230" r="1.5"/><circle cx="260" cy="240" r="1.5"/><circle cx="280" cy="250" r="1.5"/><circle cx="300" cy="240" r="1.5"/>
        <circle cx="270" cy="270" r="1.5"/><circle cx="290" cy="280" r="1.5"/><circle cx="300" cy="295" r="1.5"/>

        <!-- South America -->
        <circle cx="320" cy="330" r="1.5"/><circle cx="340" cy="350" r="1.5"/><circle cx="360" cy="360" r="1.5"/><circle cx="380" cy="370" r="1.5"/>
        <circle cx="330" cy="370" r="1.5"/><circle cx="350" cy="390" r="1.5"/><circle cx="370" cy="410" r="1.5"/><circle cx="390" cy="410" r="1.5"/>
        <circle cx="340" cy="420" r="1.5"/><circle cx="360" cy="440" r="1.5"/><circle cx="380" cy="450" r="1.5"/>
        <circle cx="350" cy="470" r="1.5"/><circle cx="360" cy="500" r="1.5"/>

        <!-- Europe -->
        <circle cx="550" cy="140" r="1.5"/><circle cx="570" cy="130" r="1.5"/><circle cx="590" cy="135" r="1.5"/><circle cx="610" cy="140" r="1.5"/>
        <circle cx="540" cy="160" r="1.5"/><circle cx="560" cy="165" r="1.5"/><circle cx="580" cy="170" r="1.5"/><circle cx="600" cy="170" r="1.5"/><circle cx="620" cy="165" r="1.5"/>
        <circle cx="545" cy="180" r="1.5"/><circle cx="565" cy="190" r="1.5"/><circle cx="585" cy="195" r="1.5"/><circle cx="605" cy="190" r="1.5"/>

        <!-- Africa -->
        <circle cx="550" cy="230" r="1.5"/><circle cx="570" cy="235" r="1.5"/><circle cx="590" cy="240" r="1.5"/><circle cx="620" cy="245" r="1.5"/>
        <circle cx="540" cy="265" r="1.5"/><circle cx="565" cy="275" r="1.5"/><circle cx="595" cy="285" r="1.5"/><circle cx="630" cy="290" r="1.5"/>
        <circle cx="575" cy="320" r="1.5"/><circle cx="605" cy="335" r="1.5"/><circle cx="635" cy="340" r="1.5"/>
        <circle cx="590" cy="370" r="1.5"/><circle cx="620" cy="385" r="1.5"/>
        <circle cx="605" cy="420" r="1.5"/><circle cx="615" cy="450" r="1.5"/>

        <!-- Asia -->
        <circle cx="680" cy="150" r="1.5"/><circle cx="710" cy="145" r="1.5"/><circle cx="750" cy="140" r="1.5"/><circle cx="800" cy="145" r="1.5"/><circle cx="850" cy="150" r="1.5"/>
        <circle cx="670" cy="180" r="1.5"/><circle cx="710" cy="185" r="1.5"/><circle cx="760" cy="180" r="1.5"/><circle cx="810" cy="185" r="1.5"/><circle cx="860" cy="195" r="1.5"/><circle cx="900" cy="190" r="1.5"/>
        <circle cx="680" cy="220" r="1.5"/><circle cx="730" cy="230" r="1.5"/><circle cx="780" cy="235" r="1.5"/><circle cx="820" cy="230" r="1.5"/><circle cx="880" cy="240" r="1.5"/><circle cx="920" cy="225" r="1.5"/>
        <circle cx="740" cy="270" r="1.5"/><circle cx="765" cy="290" r="1.5"/><circle cx="800" cy="280" r="1.5"/><circle cx="840" cy="275" r="1.5"/>

        <!-- Southeast Asia & Oceania -->
        <circle cx="805" cy="305" r="1.5"/><circle cx="830" cy="315" r="1.5"/><circle cx="860" cy="310" r="1.5"/>
        <circle cx="820" cy="340" r="1.5"/><circle cx="850" cy="350" r="1.5"/><circle cx="880" cy="345" r="1.5"/>
        <circle cx="920" cy="420" r="1.5"/><circle cx="950" cy="415" r="1.5"/><circle cx="970" cy="430" r="1.5"/>
        <circle cx="910" cy="450" r="1.5"/><circle cx="935" cy="470" r="1.5"/><circle cx="965" cy="460" r="1.5"/>
    </g>
</svg>

