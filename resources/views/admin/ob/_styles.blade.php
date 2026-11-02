{{-- Shared styles for the OB Admin panel pages (included from each page's @push('styles')). --}}
<style>
    .flash{border-radius:var(--radius-md);padding:11px 14px;font-size:13px;font-weight:600;margin-bottom:16px;}
    .flash.ok{background:var(--green-100);color:#0f4433;border:1px solid #cdeedd;}
    .flash.err{background:#f4e4de;color:#b4502c;border:1px solid #ecd2c8;}
    .back-link{display:inline-block;font-size:13px;font-weight:700;color:#fff;text-shadow:0 2px 10px rgba(7,35,26,.4);margin-bottom:14px;}

    .note-banner{background:var(--orange-100);color:#7a4412;border:1px solid rgba(239,141,61,.35);border-radius:12px;padding:11px 14px;font-size:12.5px;font-weight:600;margin-bottom:16px;line-height:1.5;}
    .note-banner.green{background:var(--green-100);color:var(--green-800);border-color:#cdeedd;}

    .view-tabs{display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap;}
    .view-tab{font-size:13px;font-weight:700;padding:8px 14px;border-radius:10px;color:var(--ink-600);background:rgba(255,255,255,.85);border:1px solid var(--line);}
    .view-tab.active{background:var(--green-600);color:#fff;border-color:var(--green-600);}
    .view-tab .n{margin-left:6px;font-size:11px;opacity:.8;}

    .filter-bar{display:flex;gap:10px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:14px;}
    .filter-form{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;}
    .filter-form .f{display:flex;flex-direction:column;gap:4px;}
    .filter-form label{font-size:11px;font-weight:700;color:var(--ink-600);}
    .f-input{padding:8px 11px;border:1px solid var(--line);border-radius:10px;font-family:inherit;font-size:13px;background:rgba(255,255,255,.9);color:var(--ink-900);min-width:130px;}
    .f-btn{padding:9px 14px;border-radius:10px;border:none;background:var(--ink-900);color:#fff;font-weight:700;font-size:12.5px;cursor:pointer;}
    .f-link{padding:9px 14px;border-radius:10px;border:1px solid var(--line);background:#fff;color:var(--ink-600);font-weight:700;font-size:12.5px;}
    .f-check{display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:700;color:var(--ink-600);padding-bottom:9px;}

    .chips{display:flex;gap:6px;flex-wrap:wrap;}
    .chip{font-size:12px;font-weight:700;padding:6px 11px;border-radius:999px;background:var(--green-050);border:1px solid var(--line);color:var(--ink-600);}
    .chip.active{background:var(--ink-900);color:#fff;border-color:var(--ink-900);}
    .chip .n{margin-left:5px;opacity:.7;font-weight:600;}

    .ob-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px;}
    @media (max-width:980px){.ob-stats{grid-template-columns:repeat(2,1fr);}}
    @media (max-width:520px){.ob-stats{grid-template-columns:1fr;}}
    .ob-stat .lbl{font-size:12px;font-weight:700;color:var(--ink-600);}
    .ob-stat .val{font-size:24px;font-weight:800;letter-spacing:-.4px;margin:3px 0 2px;}
    .ob-stat .hint{font-size:11.5px;font-weight:600;color:var(--ink-400);}

    .table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch;}
    .ob-table{width:100%;border-collapse:collapse;min-width:860px;}
    .ob-table th{text-align:left;font-size:11px;font-weight:800;color:var(--ink-600);padding:9px 10px;border-bottom:1px solid var(--line);white-space:nowrap;}
    .ob-table td{padding:12px 10px;font-size:13px;border-bottom:1px solid var(--line);vertical-align:middle;}
    .ob-table tr:last-child td{border-bottom:none;}
    .ob-table td.num,.ob-table th.num{text-align:right;white-space:nowrap;}
    .sub{font-size:11.5px;font-weight:600;color:var(--ink-600);}
    .trunc{max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}

    .pill{font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;white-space:nowrap;display:inline-block;}
    .p-pending,.p-open,.p-warn{background:var(--orange-100);color:#a85b1f;}
    .p-approved,.p-under_review{background:#e6f0fb;color:#2b5d9b;}
    .p-released,.p-resolved,.p-ok{background:var(--green-100);color:var(--green-700);}
    .p-rejected,.p-cancelled,.p-bad{background:#f4e4de;color:#b4502c;}
    .p-scheduled{background:var(--green-600);color:#fff;}
    .p-finished,.p-gray{background:#eef2f0;color:var(--ink-600);}
    .unit-pill{font-size:11.5px;font-weight:700;padding:4px 10px;border-radius:999px;background:var(--green-050);color:var(--green-700);border:1px solid var(--green-100);}

    .row-actions{display:flex;gap:6px;flex-wrap:wrap;}
    .btn-sm{font-size:11.5px;font-weight:700;padding:6px 12px;border-radius:999px;cursor:pointer;border:1px solid transparent;white-space:nowrap;display:inline-block;font-family:inherit;}
    .b-primary{background:var(--green-600);color:#fff;}
    .b-primary:hover{background:var(--green-700);}
    .b-ghost{background:#fff;color:var(--green-700);border-color:var(--green-100);}
    .b-ghost:hover{background:var(--green-050);}
    .b-danger{background:#fff;color:#b4502c;border-color:#f4e4de;}
    .b-danger:hover{background:#f4e4de;}
    .b-dark{background:var(--ink-900);color:#fff;}

    .pager{display:flex;justify-content:space-between;align-items:center;margin-top:14px;font-size:12.5px;color:var(--ink-600);font-weight:600;}
    .pager a{font-weight:700;color:var(--green-700);padding:6px 12px;border:1px solid var(--line);border-radius:9px;background:#fff;}
    .pager span.off{padding:6px 12px;border:1px solid var(--line);border-radius:9px;opacity:.45;}

    .ap-overlay{position:fixed;inset:0;background:rgba(7,35,26,.55);display:none;align-items:center;justify-content:center;z-index:300;padding:20px;}
    .ap-overlay.show{display:flex;}
    .ap-box{background:#fff;border-radius:var(--radius-lg);width:100%;max-width:460px;max-height:90vh;overflow-y:auto;padding:24px;box-shadow:0 24px 60px -20px rgba(7,35,26,.4);}
    .ap-title{font-size:16px;font-weight:800;margin-bottom:4px;}
    .ap-text{font-size:13px;color:var(--ink-600);line-height:1.5;margin-bottom:14px;}
    .ap-actions{display:flex;gap:10px;margin-top:6px;}
    .ap-actions button{flex:1;padding:11px 0;border-radius:11px;font-weight:700;font-size:13.5px;cursor:pointer;border:1px solid var(--line);font-family:inherit;}
    .ap-actions .b-close{background:#fff;color:var(--ink-900);}
    .ap-actions .b-go{background:var(--green-600);color:#fff;border-color:transparent;}
    .ap-actions .b-red{background:#b4502c;color:#fff;border-color:transparent;}

    .fld{margin-bottom:14px;}
    .fld label{display:block;font-size:12.5px;font-weight:700;color:var(--ink-600);margin-bottom:6px;}
    .fld input,.fld select,.fld textarea{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:10px;font-family:inherit;font-size:13.5px;background:var(--green-050);color:var(--ink-900);}
    .fld textarea{min-height:70px;resize:vertical;}
    .fld .hint{font-size:11.5px;font-weight:600;color:var(--ink-400);margin-top:5px;}
    .fld .err{color:#b4502c;font-size:11.5px;font-weight:700;margin-top:5px;}
    .fld-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    @media (max-width:520px){.fld-row{grid-template-columns:1fr;}}
    .check-row{display:flex;gap:8px;align-items:flex-start;font-size:13px;font-weight:600;margin-bottom:12px;}
    .check-row input{margin-top:3px;}

    .section-h{font-size:12px;font-weight:800;color:var(--green-700);margin:18px 0 4px;}
    .d-row{display:flex;justify-content:space-between;gap:16px;font-size:13px;padding:7px 0;border-bottom:1px solid var(--line);}
    .d-row:last-child{border-bottom:none;}
    .d-label{color:var(--ink-600);font-weight:600;flex-shrink:0;}
    .d-value{font-weight:700;text-align:right;word-break:break-word;}
    .two-col{display:grid;grid-template-columns:1fr 1fr;gap:18px;align-items:start;}
    @media (max-width:900px){.two-col{grid-template-columns:1fr;}}
    .stack{display:flex;flex-direction:column;gap:18px;}
    .empty-mini{font-size:12.5px;font-weight:600;color:var(--ink-600);padding:10px 0;}
</style>