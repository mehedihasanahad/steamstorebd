{{-- Tells clients this design is dark so they don't force-invert it. --}}
<meta name="color-scheme" content="dark">
<meta name="supported-color-schemes" content="dark">
<style>
    /* Every panel colour here is OPAQUE on purpose. Translucent rgba() panels
       look right over the dark body but collapse to near-white when a mail
       client drops the body background — which takes the light text with them.
       The dark canvas itself is painted by a wrapper table with a bgcolor
       attribute, not by `body`, because clients strip body styles far more
       often than they strip table attributes. */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { background-color: #030711; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: #e5e7eb; }
    .email-bg { background-color: #030711; width: 100%; }
    .container { max-width: 600px; margin: 0 auto; text-align: left; }
    .header { background-color: #071428; border-radius: 16px; padding: 40px 32px; text-align: center; margin-bottom: 24px; border: 1px solid #14305C; }
    .logo { font-size: 24px; font-weight: 800; color: #ffffff; margin-bottom: 4px; }
    .logo span { color: #4B8FEF; }
    .tagline { color: #9ca3af; font-size: 13px; margin-top: 4px; }
    .ref-badge { display: inline-block; background-color: #0E2133; border: 1px solid #1C3A57; color: #4B8FEF; font-family: monospace; font-weight: 700; padding: 6px 16px; border-radius: 999px; font-size: 14px; margin-top: 12px; }
    .info-box { border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 24px; }
    .info-box h2 { font-size: 20px; margin-bottom: 6px; }
    .info-box p { color: #cbd5e1; font-size: 14px; line-height: 1.7; }
    .info-pending  { background-color: #241D07; border: 1px solid #5B4710; }
    .info-pending h2  { color: #FBBF24; }
    .info-success  { background-color: #07240F; border: 1px solid #14532D; }
    .info-success h2  { color: #4ADE80; }
    .info-neutral  { background-color: #151B24; border: 1px solid #2B3542; }
    .info-neutral h2  { color: #E2E8F0; }
    .section { background-color: #111827; border: 1px solid #374151; border-radius: 16px; padding: 24px; margin-bottom: 20px; }
    .section h3 { color: #ffffff; font-size: 16px; font-weight: 700; margin-bottom: 16px; }
    .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #374151; font-size: 14px; }
    .detail-row:last-child { border-bottom: none; }
    .detail-row .label { color: #9ca3af; }
    .detail-row .value { color: #ffffff; font-weight: 600; }
    .reason-box { background-color: #2A1114; border: 1px solid #7F2426; border-radius: 12px; padding: 18px 20px; margin-bottom: 20px; }
    .reason-label { color: #FCA5A5; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }
    .reason-text { color: #F1F5F9; font-size: 15px; line-height: 1.8; }
    .steps { background-color: #1f2937; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
    .steps h4 { color: #ffffff; font-size: 14px; font-weight: 600; margin-bottom: 12px; }
    .steps ol, .steps ul { color: #cbd5e1; font-size: 13px; line-height: 2.2; padding-left: 20px; }
    .cta { text-align: center; margin-bottom: 20px; }
    .cta a { display: inline-block; background-color: #2563EB; color: #ffffff !important; text-decoration: none; font-weight: 700; font-size: 14px; padding: 14px 32px; border-radius: 12px; }
    .footer { text-align: center; padding: 24px 0; }
    .footer p { color: #9ca3af; font-size: 12px; line-height: 1.8; }
    .footer a { color: #4B8FEF; text-decoration: none; }
</style>
