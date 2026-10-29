<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') · SSComplexPlatform Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{
            --green-950:#07231a;
            --green-900:#0b3327;
            --green-800:#0f4433;
            --green-700:#146245;
            --green-600:#178a5e;
            --green-500:#1fae74;
            --green-100:#e8f7ef;
            --green-050:#f3fbf7;
            --orange-500:#ef8d3d;
            --orange-100:#fdece0;
            --ink-900:#0e1c17;
            --ink-600:#34443d;
            --ink-400:#55655e;
            --line:#e4ece8;
            --card:#ffffff;
            --radius-lg:18px;
            --radius-md:12px;
            --shadow:0 1px 2px rgba(14,28,23,.04), 0 8px 24px -12px rgba(14,28,23,.10);
        }
        *{box-sizing:border-box;}
        html{-webkit-text-size-adjust:100%;}
        body{
            margin:0;
            font-family:'Manrope',-apple-system,BlinkMacSystemFont,sans-serif;
            background:var(--green-050);
            color:var(--ink-900);
            position:relative;
            overflow-x:hidden;
            -webkit-font-smoothing:antialiased;
        }
        body::before{
            content:'';
            position:fixed;
            inset:0;
            z-index:-1;
            background:
                linear-gradient(180deg, rgba(7,35,26,.62), rgba(7,35,26,.72)),
                url('{{ asset('images/hero/SSCfrontview.jpg') }}') center / cover no-repeat fixed;
        }
        a{text-decoration:none;color:inherit;}
        img{max-width:100%;}

        /* ---------- Sidebar: floating glass panel, overlay, closed by default on every screen size ---------- */
        .sidebar{
            width:272px;
            max-width:calc(100vw - 24px);
            position:fixed;
            left:12px;top:12px;bottom:12px;
            z-index:200;
            display:flex;
            flex-direction:column;
            gap:18px;
            padding:18px 14px 14px;
            color:#eaf6f0;
            background:
                linear-gradient(160deg, rgba(31,174,116,.22), rgba(7,35,26,0) 45%),
                rgba(7,35,26,.62);
            backdrop-filter:blur(26px) saturate(170%);
            -webkit-backdrop-filter:blur(26px) saturate(170%);
            border:1px solid rgba(255,255,255,.16);
            border-radius:24px;
            box-shadow:0 24px 60px -18px rgba(0,0,0,.55), inset 0 1px 0 rgba(255,255,255,.14);
            overflow-y:auto;
            overscroll-behavior:contain;
            scrollbar-width:none;
            transform:translateX(calc(-100% - 24px));
            opacity:0;
            visibility:hidden;
            transition:transform .3s cubic-bezier(.22,.8,.3,1), opacity .25s ease, visibility 0s linear .3s;
        }
        .sidebar::-webkit-scrollbar{display:none;}
        .sidebar.open{
            transform:none;
            opacity:1;
            visibility:visible;
            transition:transform .3s cubic-bezier(.22,.8,.3,1), opacity .25s ease, visibility 0s;
        }
        .sidebar-overlay{
            display:none;
            position:fixed;inset:0;
            background:rgba(7,35,26,.45);
            backdrop-filter:blur(3px);
            -webkit-backdrop-filter:blur(3px);
            z-index:190;
        }
        .sidebar-overlay.show{display:block;}

        .brand{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:0 4px;}
        .brand-left{display:flex;align-items:center;gap:11px;min-width:0;}
        .brand-mark{
            width:34px;height:34px;border-radius:11px;flex-shrink:0;
            background:linear-gradient(135deg,var(--green-500),var(--green-700));
            box-shadow:0 8px 20px -6px rgba(31,174,116,.8), inset 0 1px 0 rgba(255,255,255,.35);
        }
        .brand-name{font-weight:800;font-size:14.5px;letter-spacing:.2px;line-height:1.2;}
        .brand-sub{font-size:11px;font-weight:600;color:rgba(234,246,240,.6);margin-top:1px;}
        .sidebar-close{
            width:30px;height:30px;border-radius:10px;flex-shrink:0;
            background:rgba(255,255,255,.08);
            border:1px solid rgba(255,255,255,.1);
            color:#eaf6f0;display:flex;align-items:center;justify-content:center;
            font-size:13px;cursor:pointer;
            transition:background .15s ease;
        }
        .sidebar-close:hover{background:rgba(255,255,255,.16);}

        .workspace{
            background:rgba(255,255,255,.07);
            border:1px solid rgba(255,255,255,.1);
            border-radius:14px;
            padding:10px 12px;
            display:flex;
            align-items:center;
            gap:10px;
            font-size:13px;
        }
        .workspace .dot{
            width:8px;height:8px;border-radius:50%;flex-shrink:0;
            background:var(--green-500);
            box-shadow:0 0 0 4px rgba(31,174,116,.22);
        }
        .workspace .ws-label{color:rgba(234,246,240,.6);font-size:11px;display:block;}
        .workspace .ws-name{font-weight:700;}

        .search-box{
            display:flex;align-items:center;gap:9px;
            background:rgba(255,255,255,.07);
            border:1px solid rgba(255,255,255,.1);
            border-radius:14px;
            padding:10px 12px;
            font-size:13px;
            color:rgba(234,246,240,.65);
        }
        .search-box .s-ic{font-size:13px;opacity:.8;}
        .search-box kbd{
            margin-left:auto;font-family:inherit;font-size:10px;
            background:rgba(255,255,255,.1);
            padding:2px 6px;border-radius:6px;color:#d3e2db;
        }

        .nav-group{display:flex;flex-direction:column;}
        .nav-section-label{
            font-size:10.5px;font-weight:700;letter-spacing:.09em;
            color:rgba(234,246,240,.45);
            margin:0 0 8px 10px;
        }
        .nav{display:flex;flex-direction:column;gap:4px;}
        .nav-item{
            position:relative;
            display:flex;align-items:center;gap:12px;
            padding:7px 10px;border-radius:14px;
            font-size:13.5px;font-weight:600;color:#d3e2db;
            cursor:pointer;
            border:1px solid transparent;
            transition:background .18s ease, color .18s ease, transform .18s ease;
        }
        .nav-item .ic{
            width:32px;height:32px;border-radius:10px;flex-shrink:0;
            display:flex;align-items:center;justify-content:center;
            font-size:15px;
            background:rgba(255,255,255,.07);
            border:1px solid rgba(255,255,255,.08);
            transition:background .18s ease, border-color .18s ease;
        }
        .nav-item:hover{background:rgba(255,255,255,.09);color:#fff;transform:translateX(2px);}
        .nav-item:hover .ic{background:rgba(255,255,255,.14);}
        .nav-item.active{
            color:#fff;
            background:linear-gradient(135deg, rgba(31,174,116,.55), rgba(23,138,94,.30));
            border-color:rgba(255,255,255,.18);
            box-shadow:0 10px 22px -12px rgba(31,174,116,.9), inset 0 1px 0 rgba(255,255,255,.2);
        }
        .nav-item.active:hover{transform:none;}
        .nav-item.active .ic{background:rgba(255,255,255,.2);border-color:rgba(255,255,255,.25);}
        .nav-item.active::before{
            content:'';position:absolute;left:-8px;top:50%;transform:translateY(-50%);
            width:4px;height:20px;border-radius:0 4px 4px 0;background:var(--green-500);
        }
        .nav-item .badge{
            margin-left:auto;background:var(--orange-500);color:#fff;
            font-size:10px;font-weight:700;border-radius:999px;
            padding:2px 8px;
        }
        .nav-item .badge.new{background:#fff;color:var(--green-800);}

        .sidebar-bottom{margin-top:auto;display:flex;flex-direction:column;gap:12px;}
        .user-chip{
            display:flex;align-items:center;gap:11px;
            padding:10px;
            background:rgba(255,255,255,.07);
            border:1px solid rgba(255,255,255,.1);
            border-radius:16px;
        }
        .avatar{
            width:36px;height:36px;border-radius:11px;flex-shrink:0;
            background:linear-gradient(135deg,var(--green-500),var(--orange-500));
            display:flex;align-items:center;justify-content:center;
            font-size:14px;font-weight:800;color:#fff;
            box-shadow:inset 0 1px 0 rgba(255,255,255,.3);
        }
        .user-chip .u-info{flex:1;min-width:0;}
        .user-chip .u-name{font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .user-chip .u-id{font-size:11px;color:rgba(234,246,240,.6);}
        .logout-btn{
            width:34px;height:34px;border-radius:11px;flex-shrink:0;
            display:flex;align-items:center;justify-content:center;
            background:rgba(239,141,61,.16);color:var(--orange-500);
            border:1px solid rgba(239,141,61,.3);cursor:pointer;font-size:14px;
            transition:background .15s ease;
        }
        .logout-btn:hover{background:rgba(239,141,61,.28);}

        @media (prefers-reduced-motion: reduce){
            .sidebar,.sidebar.open,.nav-item,.nav-item .ic{transition:none;}
            .nav-item:hover{transform:none;}
        }

        /* ---------- Logout confirm modal ---------- */
        .modal-overlay{
            position:fixed;inset:0;background:rgba(7,35,26,.55);
            display:none;align-items:center;justify-content:center;
            z-index:300;padding:20px;
        }
        .modal-overlay.show{display:flex;}
        .modal-box{
            background:#fff;border-radius:var(--radius-lg);
            width:100%;max-width:360px;padding:26px 24px 22px;
            box-shadow:0 24px 60px -20px rgba(7,35,26,.4);
            text-align:center;
        }
        .modal-icon{
            width:52px;height:52px;border-radius:50%;margin:0 auto 16px;
            background:var(--orange-100);color:var(--orange-500);
            display:flex;align-items:center;justify-content:center;font-size:22px;
        }
        .modal-title{font-size:16px;font-weight:800;margin-bottom:8px;}
        .modal-text{font-size:13px;color:var(--ink-600);line-height:1.5;margin-bottom:22px;}
        .modal-actions{display:flex;gap:10px;}
        .modal-actions button{
            flex:1;padding:11px 0;border-radius:11px;font-weight:700;font-size:13.5px;
            cursor:pointer;border:1px solid var(--line);
        }
        .btn-cancel{background:#fff;color:var(--ink-900);}
        .btn-cancel:hover{background:var(--green-050);}
        .btn-logout{background:var(--orange-500);color:#fff;border-color:var(--orange-500);}
        .btn-logout:hover{background:#e07d2e;}

        /* ---------- Main (full width — sidebar is an overlay, not inline) ---------- */
        .main{padding:26px 30px 40px;max-width:1320px;margin:0 auto;min-width:0;}
        .topbar{
            display:flex;align-items:center;justify-content:space-between;
            margin-bottom:22px;gap:12px;
        }
        .topbar-left{display:flex;align-items:center;gap:12px;min-width:0;}
        .page-title{font-size:22px;font-weight:800;letter-spacing:-.2px;color:#fff;text-shadow:0 2px 10px rgba(7,35,26,.4);}
        .topbar-actions{display:flex;align-items:center;gap:14px;}
        .icon-btn{
            width:38px;height:38px;border-radius:11px;
            background:rgba(255,255,255,.85);
            backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);
            border:1px solid rgba(255,255,255,.5);display:flex;align-items:center;justify-content:center;
            box-shadow:var(--shadow);position:relative;
        }
        .icon-btn .dot{
            position:absolute;top:8px;right:8px;width:6px;height:6px;
            border-radius:50%;background:var(--orange-500);
        }
        .btn-primary{
            background:var(--ink-900);color:#fff;border:none;
            padding:11px 16px;border-radius:11px;font-weight:700;font-size:13px;
            display:flex;align-items:center;gap:8px;cursor:pointer;
            white-space:nowrap;
            box-shadow:0 8px 20px -8px rgba(7,35,26,.55);
        }
        .hamburger-btn{
            width:38px;height:38px;border-radius:11px;
            background:rgba(255,255,255,.85);
            backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);
            border:1px solid rgba(255,255,255,.5);display:flex;align-items:center;justify-content:center;
            box-shadow:var(--shadow);font-size:16px;cursor:pointer;flex-shrink:0;
        }

        /* shared glass card */
        .card{
            background:rgba(255,255,255,.80);
            backdrop-filter:blur(20px) saturate(160%);
            -webkit-backdrop-filter:blur(20px) saturate(160%);
            border:1px solid rgba(255,255,255,.45);
            border-radius:var(--radius-lg);
            box-shadow:0 8px 32px -8px rgba(7,35,26,.35);
            padding:20px;
            min-width:0;
        }
        .card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;}
        .card-title{font-size:14.5px;font-weight:800;}
        .dropdown-chip{
            font-size:12px;font-weight:700;color:var(--ink-600);
            background:var(--green-050);border:1px solid var(--line);
            padding:6px 11px;border-radius:9px;display:flex;align-items:center;gap:6px;cursor:pointer;
        }

        .trend{font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:4px;}
        .trend.up{color:var(--green-600);}
        .trend.down{color:var(--orange-500);}
        .muted{color:var(--ink-400);font-weight:600;}

        .empty-state{
            display:flex;flex-direction:column;align-items:center;justify-content:center;
            gap:6px;padding:34px 10px;color:var(--ink-400);text-align:center;
        }
        .empty-state .e-icon{
            width:38px;height:38px;border-radius:11px;background:var(--green-100);
            display:flex;align-items:center;justify-content:center;color:var(--green-600);font-size:17px;
        }
        .empty-state .e-title{font-weight:700;color:var(--ink-600);font-size:13px;}
        .empty-state .e-sub{font-size:12px;}

        /* wide tables scroll inside their own box instead of breaking the page */
        .table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch;}

        /* readability on glass: text inside cards, charts and tables never fades out.
           :where() keeps these as low-priority defaults so page styles can still override them. */
        .card{color:var(--ink-900);}
        :where(.card) th{color:var(--ink-600);font-weight:800;}
        :where(.card) td{color:var(--ink-900);}
        .card .muted,.card .sub{color:var(--ink-600);}

        @media (max-width: 560px){
            .main{padding:18px 16px 32px;}
            .page-title{font-size:19px;}
            .card{padding:16px;}
            .btn-primary span.btn-label{display:none;}
            .btn-primary{padding:11px;}
            .topbar-actions{gap:10px;}
        }
    </style>
    @stack('styles')
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="brand">
        <div class="brand-left">
            <div class="brand-mark"></div>
            <div>
                <div class="brand-name">SSComplexPlatform</div>
                <div class="brand-sub">Admin Console</div>
            </div>
        </div>
        <button type="button" class="sidebar-close" onclick="closeSidebar()" aria-label="Close menu">✕</button>
    </div>

    <div class="workspace">
        <span class="dot"></span>
        <div>
            <span class="ws-label">Team Workspace</span>
            <span class="ws-name">SSC Admin</span>
        </div>
    </div>

    <div class="search-box">
        <span class="s-ic">🔍</span>
        <span>Search for...</span>
        <kbd>⌘F</kbd>
    </div>

    <div class="nav-group">
        <div class="nav-section-label">MANAGE</div>
        <nav class="nav">
            <a class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><span class="ic">▦</span> Dashboard</a>
            <a class="nav-item {{ request()->routeIs('admin.appointments.index') ? 'active' : '' }}" href="{{ route('admin.appointments.index') }}"><span class="ic">📅</span> Appointments</a>
            <a class="nav-item {{ request()->routeIs('admin.appointments.scheduled') ? 'active' : '' }}" href="{{ route('admin.appointments.scheduled') }}"><span class="ic">✔</span> Scheduled</a>
            <a class="nav-item {{ request()->routeIs('admin.posters*') ? 'active' : '' }}" href="{{ route('admin.posters.index') }}"><span class="ic">🖼</span> Posters</a>
            {{-- <a class="nav-item {{ request()->routeIs('admin.staff*') ? 'active' : '' }}" href="{{ route('admin.staff.index') }}"><span class="ic">👤</span> Staff Accounts</a> --}}
        </nav>
    </div>

    <div class="nav-group">
        <div class="nav-section-label">INSIGHTS &amp; HELP</div>
        <nav class="nav">
            <a class="nav-item"><span class="ic">◐</span> Analytics</a>
            <a class="nav-item"><span class="ic">▤</span> Team Structure</a>
            <a class="nav-item"><span class="ic">▥</span> Reports <span class="badge">{{ $pendingReportsCount ?? 0 }}</span></a>
            <a class="nav-item"><span class="ic">◈</span> Support <span class="badge new">NEW</span></a>
        </nav>
    </div>

    <div class="sidebar-bottom">
        <a class="nav-item"><span class="ic">⚙</span> Settings</a>
        <div class="user-chip">
            <div class="avatar">{{ strtoupper(substr(auth()->user()->first_name ?? 'A', 0, 1)) }}</div>
            <div class="u-info">
                <div class="u-name">{{ auth()->user()->first_name ?? 'Admin User' }}</div>
                <div class="u-id">#{{ auth()->user()->id ?? '0000' }}</div>
            </div>
            <button type="button" class="logout-btn" title="Log out" onclick="openLogoutModal()" aria-label="Log out">⏻</button>
        </div>
    </div>
</aside>

<div class="modal-overlay" id="logoutModal">
    <div class="modal-box">
        <div class="modal-icon">⏻</div>
        <div class="modal-title">Log out?</div>
        <div class="modal-text">You are currently logged in. Are you sure you want to log out?</div>
        <div class="modal-actions">
            <button type="button" class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
            <button type="button" class="btn-logout" onclick="document.getElementById('logoutForm').submit()">Logout</button>
        </div>
    </div>
</div>

<form id="logoutForm" method="POST" action="{{ route('logout') }}" style="display:none;">
    @csrf
</form>

<main class="main">
    <div class="topbar">
        <div class="topbar-left">
            <button type="button" class="hamburger-btn" onclick="openSidebar()" aria-label="Open menu">☰</button>
            <div class="page-title">@yield('title', 'Dashboard')</div>
        </div>
        <div class="topbar-actions">
            <div class="icon-btn">🔔<span class="dot"></span></div>
            <button class="btn-primary">+ <span class="btn-label">Add Custom Widget</span></button>
        </div>
    </div>

    @yield('content')
</main>

<script>
    function openSidebar(){
        document.getElementById('sidebar').classList.add('open');
        document.getElementById('sidebarOverlay').classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar(){
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
        document.body.style.overflow = '';
    }
    function openLogoutModal(){
        document.getElementById('logoutModal').classList.add('show');
    }
    function closeLogoutModal(){
        document.getElementById('logoutModal').classList.remove('show');
    }
    document.getElementById('logoutModal').addEventListener('click', function(e){
        if (e.target === this) closeLogoutModal();
    });
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape'){ closeLogoutModal(); closeSidebar(); }
    });
</script>
@stack('scripts')
</body>
</html>