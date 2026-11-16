<svg class="scene" viewBox="0 0 680 440" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
  <defs>
    <linearGradient id="hs-wick" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#e9d6ae"/><stop offset="1" stop-color="#c9ad78"/></linearGradient>
    <linearGradient id="hs-green" x1="0" x2="1"><stop offset="0" stop-color="#7fc243"/><stop offset="1" stop-color="#4c9a2a"/></linearGradient>
    <linearGradient id="hs-sh" x1="0" x2="1"><stop offset="0" stop-color="#000" stop-opacity=".12"/><stop offset=".4" stop-color="#fff" stop-opacity=".3"/><stop offset="1" stop-color="#000" stop-opacity=".18"/></linearGradient>
    <pattern id="hs-weave" width="16" height="12" patternUnits="userSpaceOnUse"><path d="M0 6h16M4 0v12M12 0v12" stroke="#a98a55" stroke-width="1.600" opacity=".45" fill="none"/></pattern>
  </defs>
  <ellipse cx="360" cy="420" rx="300" ry="14" fill="#000" opacity=".10"/>

  {{-- plant right --}}
  <g class="sway" style="transform-origin:640px 120px">
    <path d="M660 10c-60 6-120 34-150 96 40-6 90-30 150-96z" fill="#3f8a2a"/>
    <path d="M640 70c-40 0-84 20-108 66 36-4 72-24 108-66z" fill="#58a334"/>
    <path d="M680 40c-24 22-50 40-70 70 36-6 60-34 70-70z" fill="#2f7620"/>
  </g>

  {{-- tissue roll --}}
  <g>
    <rect x="520" y="140" width="120" height="196" rx="14" fill="#e9f1ea"/>
    <rect x="520" y="140" width="120" height="196" rx="14" fill="url(#hs-sh)"/>
    <ellipse cx="580" cy="142" rx="60" ry="12" fill="#c8ddcb"/>
    <rect x="520" y="176" width="120" height="70" fill="#5fb4a6"/>
    <text x="580" y="222" text-anchor="middle" font-family="Inter,Arial" font-weight="800" font-size="26" fill="#fff">SoftCare</text>
    <text x="580" y="240" text-anchor="middle" font-family="Inter,Arial" font-weight="600" font-size="9" fill="#e2f4f0">Toilet Tissue</text>
  </g>

  {{-- yellow bottle (Fresh Clean) --}}
  <g class="bob" style="animation-delay:-1s">
    <rect x="428" y="96" width="30" height="26" rx="5" fill="#3d3d3d"/>
    <rect x="408" y="112" width="40" height="12" rx="3" fill="#2f2f2f"/>
    <path d="M396 150q0-24 24-24h14q24 0 24 24v176q0 16-18 16h-26q-18 0-18-16z" fill="#f6df2e"/>
    <path d="M396 150q0-24 24-24h14q24 0 24 24v176q0 16-18 16h-26q-18 0-18-16z" fill="url(#hs-sh)"/>
    <rect x="396" y="186" width="60" height="112" fill="#fff" opacity=".95"/>
    <text x="426" y="222" text-anchor="middle" font-family="Inter,Arial" font-weight="800" font-size="17" fill="#3c8a24">Fresh</text>
    <text x="426" y="242" text-anchor="middle" font-family="Inter,Arial" font-weight="800" font-size="17" fill="#3c8a24">Clean</text>
    <text x="426" y="262" text-anchor="middle" font-family="Inter,Arial" font-weight="700" font-size="11" fill="#d9a800">Tissher</text>
    <circle cx="426" cy="282" r="9" fill="#f6df2e"/>
  </g>

  {{-- white jug --}}
  <g>
    <path d="M290 40q0-20 24-20h30q24 0 24 20l24 40v230q0 20-22 20H288q-22 0-22-20V80z" fill="#f5f7f4"/>
    <path d="M290 40q0-20 24-20h30q24 0 24 20l24 40v230q0 20-22 20H288q-22 0-22-20V80z" fill="url(#hs-sh)"/>
    <rect x="296" y="4" width="48" height="26" rx="6" fill="#4c9a2a"/>
    <path d="M360 56q34 6 34 44v60" stroke="#e6ebe5" stroke-width="12" fill="none" stroke-linecap="round"/>
    <rect x="266" y="150" width="108" height="100" fill="#7fc243" opacity=".9"/>
    <path d="M300 210c24-30 54-30 70-4" stroke="#fff" stroke-width="4" fill="none" opacity=".7"/>
  </g>

  {{-- green pump bottle --}}
  <g class="bob">
    <rect x="228" y="52" width="40" height="14" rx="4" fill="#3d7d22"/>
    <path d="M248 52V30h38" stroke="#3d7d22" stroke-width="12" fill="none" stroke-linecap="round"/>
    <rect x="236" y="64" width="24" height="18" fill="#3d7d22"/>
    <path d="M212 112q0-30 32-30h0q32 0 32 30v196q0 18-20 18h-24q-20 0-20-18z" fill="url(#hs-green)"/>
    <path d="M212 112q0-30 32-30h0q32 0 32 30v196q0 18-20 18h-24q-20 0-20-18z" fill="url(#hs-sh)"/>
    <rect x="212" y="170" width="64" height="86" fill="#fff" opacity=".93"/>
    <text x="244" y="206" text-anchor="middle" font-family="Inter,Arial" font-weight="800" font-size="13" fill="#3c8a24">Fresh</text>
    <text x="244" y="224" text-anchor="middle" font-family="Inter,Arial" font-weight="700" font-size="9" fill="#6e6e6e">Hand Wash</text>
  </g>

  {{-- toothbrush cup --}}
  <g class="bob" style="animation-delay:-2s">
    <path d="M86 70l10-4 16 70" stroke="#4aa8d8" stroke-width="9" stroke-linecap="round" fill="none"/>
    <path d="M118 62l10-3 14 76" stroke="#3a9acb" stroke-width="9" stroke-linecap="round" fill="none"/>
    <path d="M150 64l10 2-4 70" stroke="#69b8e0" stroke-width="9" stroke-linecap="round" fill="none"/>
    <path d="M74 134h104l-10 134q-2 16-18 16h-48q-16 0-18-16z" fill="#fbfcfb"/>
    <path d="M74 134h104l-10 134q-2 16-18 16h-48q-16 0-18-16z" fill="url(#hs-sh)"/>
  </g>

  {{-- plant pot --}}
  <g class="sway" style="transform-origin:160px 250px">
    <path d="M140 240c-30-30-34-70-8-96 8 30 22 58 8 96zM160 240c0-44 16-84 44-110 4 44-6 80-44 110zM170 244c20-26 44-40 70-40-16 24-40 40-70 40z" fill="#3f8a2a"/>
    <path d="M122 240h76l-8 56q-2 14-16 14h-28q-14 0-16-14z" fill="#f7f7f5"/>
  </g>

  {{-- basket --}}
  <g>
    <path d="M190 300h340l-26 112q-4 12-16 12H232q-12 0-16-12z" fill="url(#hs-wick)"/>
    <path d="M190 300h340l-26 112q-4 12-16 12H232q-12 0-16-12z" fill="url(#hs-weave)"/>
    <rect x="182" y="288" width="356" height="22" rx="11" fill="#d8bf8a"/>
    <rect x="182" y="288" width="356" height="22" rx="11" fill="url(#hs-weave)"/>
  </g>

  {{-- towels --}}
  <g class="bob" style="animation-delay:-.5s">
    <rect x="240" y="326" width="260" height="34" rx="8" fill="#8cc63f"/>
    <rect x="240" y="326" width="260" height="10" rx="5" fill="#a6d95a"/>
    <rect x="228" y="358" width="280" height="36" rx="8" fill="#f4c93a"/>
    <rect x="228" y="358" width="280" height="10" rx="5" fill="#fbdb6a"/>
    <rect x="216" y="392" width="300" height="34" rx="8" fill="#e8b82c" opacity="0"/>
  </g>
  <g>
    <rect x="410" y="356" width="150" height="34" rx="6" fill="#3d8a2c"/>
    <rect x="420" y="344" width="130" height="16" rx="4" fill="#f4d526"/>
    <rect x="420" y="344" width="130" height="5" rx="2.500" fill="#fff28a"/>
    <rect x="410" y="372" width="150" height="46" rx="6" fill="#2f7a24"/>
    <rect x="410" y="372" width="150" height="8" rx="4" fill="#4a9a37"/>
  </g>
</svg>
