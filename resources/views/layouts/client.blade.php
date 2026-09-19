<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') · SSComplexPlatform</title>
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
            --ink-600:#5b6b64;
            --ink-400:#93a29c;
            --line:#e4ece8;
            --card:#ffffff;
            --radius-lg:18px;
            --radius-md:12px;
            --shadow:0 1px 2px rgba(14,28,23,.04), 0 8px 24px -12px rgba(14,28,23,.10);
        }
        *{box-sizing:border-box;}
        body{
            margin:0;
            font-family:'Manrope',-apple-system,BlinkMacSystemFont,sans-serif;
            background:var(--green-050);
            color:var(--ink-900);
            position:relative;
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

        /* ---------- Sidebar: overlay, closed by default, every screen size ---------- */
        .sidebar{
            width:248px;
            background:var(--green-950);
            color:#eaf6f0;
            padding:22px 16px;
            display:flex;
            flex-direction:column;
            gap:22px;
            position:fixed;
            left:0;top:0;
            height:100vh;
            z-index:200;
            transform:translateX(-100%);
            transition:transform .25s ease;
            box-shadow:0 0 40px rgba(0,0,0,.25);
        }
        .sidebar.open{transform:translateX(0);}
        .sidebar-overlay{
            display:none;
            position:fixed;inset:0;background:rgba(7,35,26,.5);
            z-index:190;
        }
        .sidebar-overlay.show{display:block;}

        .brand{display:flex;align-items:center;justify-content:space-between;}
        .brand-left{display:flex;align-items:center;gap:10px;}
        .brand-mark{
            width:26px;height:26px;border-radius:7px;
            background:linear-gradient(135deg,var(--green-500),var(--green-700));
            flex-shrink:0;
        }
        .brand-name{font-weight:800;font-size:15px;letter-spacing:.2px;}
        .sidebar-close{
            width:30px;height:30px;border-radius:9px;background:rgba(255,255,255,.08);
            border:none;color:#eaf6f0;display:flex;align-items:center;justify-content:center;
            font-size:14px;cursor:pointer;
        }

        .workspace{
            background:rgba(255,255,255,.06);
            border:1px solid rgba(255,255,255,.08);
            border-radius:var(--radius-md);
            padding:10px 12px;
            display:flex;
            align-items:center;
            gap:9px;
            font-size:13px;
        }
        .workspace .dot{width:8px;height:8px;border-radius:50%;background:var(--green-500);}
        .workspace .ws-label{color:#9fb3ab;font-size:11px;display:block;}
        .workspace .ws-name{font-weight:700;}

        .nav-section-label{
            font-size:10.5px;letter-spacing:.06em;color:#6f8479;
            margin:4px 0 -8px 10px;
        }
        .nav{display:flex;flex-direction:column;gap:2px;}
        .nav-item{
            display:flex;align-items:center;gap:10px;
            padding:10px 12px;border-radius:10px;
            font-size:13.5px;font-weight:600;color:#c7d6cf;
            cursor:pointer;
        }
        .nav-item .ic{width:17px;text-align:center;opacity:.85;}
        .nav-item:hover{background:rgba(255,255,255,.05);}
        .nav-item.active{
            background:var(--green-600);
            color:#fff;
            box-shadow:0 6px 16px -6px rgba(23,138,94,.7);
        }

        .sidebar-bottom{margin-top:auto;display:flex;flex-direction:column;gap:14px;}
        .user-chip{
            display:flex;align-items:center;gap:10px;
            padding:8px 6px;border-top:1px solid rgba(255,255,255,.08);
            padding-top:16px;
        }
        .avatar{
            width:32px;height:32px;border-radius:9px;
            background:linear-gradient(135deg,var(--green-500),var(--orange-500));
            flex-shrink:0;
        }
        .user-chip .u-info{flex:1;min-width:0;}
        .user-chip .u-name{font-size:13px;font-weight:700;}
        .user-chip .u-id{font-size:11px;color:#8ea299;}
        .logout-btn{
            width:32px;height:32px;border-radius:9px;flex-shrink:0;
            display:flex;align-items:center;justify-content:center;
            background:rgba(239,141,61,.14);color:var(--orange-500);
            border:1px solid rgba(239,141,61,.25);cursor:pointer;font-size:14px;
            transition:background .15s ease;
        }
        .logout-btn:hover{background:rgba(239,141,61,.24);}

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
        .main{padding:26px 30px 40px;max-width:1320px;margin:0 auto;}
        .topbar{
            display:flex;align-items:center;justify-content:space-between;
            margin-bottom:22px;gap:12px;
        }
        .topbar-left{display:flex;align-items:center;gap:12px;}
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
        .hamburger-btn{
            width:38px;height:38px;border-radius:11px;
            background:rgba(255,255,255,.85);
            backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);
            border:1px solid rgba(255,255,255,.5);display:flex;align-items:center;justify-content:center;
            box-shadow:var(--shadow);font-size:16px;cursor:pointer;flex-shrink:0;
        }

        .card{
            background:rgba(255,255,255,.58);
            backdrop-filter:blur(20px) saturate(160%);
            -webkit-backdrop-filter:blur(20px) saturate(160%);
            border:1px solid rgba(255,255,255,.45);
            border-radius:var(--radius-lg);
            box-shadow:0 8px 32px -8px rgba(7,35,26,.35);
            padding:20px;
        }
        .card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:8px;}
        .card-title{font-size:14.5px;font-weight:800;}
        .dropdown-chip{
            font-size:12px;font-weight:700;color:var(--ink-600);
            background:var(--green-050);border:1px solid var(--line);
            padding:6px 11px;border-radius:9px;display:flex;align-items:center;gap:6px;cursor:pointer;
        }
        .muted{color:var(--ink-400);font-weight:600;}
        .status-pill{font-size:11px;font-weight:700;padding:4px 10px;border-radius:999px;}
        .status-pill.ongoing{background:var(--green-100);color:var(--green-700);}
        .status-pill.finished{background:#eef2f0;color:var(--ink-600);}
        .status-pill.rescheduled{background:var(--orange-100);color:var(--orange-500);}
        .status-pill.cancelled{background:#f4e4de;color:#b4502c;}

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

        .table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch;}

        @media (max-width: 560px){
            .main{padding:18px 16px 32px;}
            .page-title{font-size:19px;}
            .card{padding:16px;}
        }
    </style>
    @stack('styles')
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="brand">
        <div class="brand-left">
            <div class="brand-mark"></div>
            <div class="brand-name">SSComplexPlatform</div>
        </div>
        <button type="button" class="sidebar-close" onclick="closeSidebar()">✕</button>
    </div>

    <div class="workspace">
        <span class="dot"></span>
        <div>
            <span class="ws-label">Client Account</span>
            <span class="ws-name">SSC Booking</span>
        </div>
    </div>

    <div>
        <div class="nav-section-label">NAVIGATION</div>
        <nav class="nav">
            <a class="nav-item {{ request()->routeIs('client.dashboard') ? 'active' : '' }}" href="{{ route('client.dashboard') }}"><span class="ic">▦</span> Dashboard</a>
            <a class="nav-item {{ request()->routeIs('client.reservations*') ? 'active' : '' }}" href="{{ route('client.reservations.create') }}"><span class="ic">➕</span> New Reservation</a>
            <a class="nav-item {{ request()->routeIs('client.appointments*') ? 'active' : '' }}" href="#"><span class="ic">📅</span> Appointments</a>
        </nav>
    </div>

    <div class="sidebar-bottom">
        <a class="nav-item"><span class="ic">⚙</span> Settings</a>
        <div class="user-chip">
            <div class="avatar"></div>
            <div class="u-info">
                <div class="u-name">{{ auth()->user()->first_name ?? 'Client' }}</div>
                <div class="u-id">#{{ auth()->user()->id ?? '0000' }}</div>
            </div>
            <button type="button" class="logout-btn" title="Log out" onclick="openLogoutModal()">⏻</button>
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
            <button type="button" class="hamburger-btn" onclick="openSidebar()">☰</button>
            <div class="page-title">@yield('title', 'Dashboard')</div>
        </div>
        <div class="topbar-actions">
            <div class="icon-btn">🔔<span class="dot"></span></div>
        </div>
    </div>

    @yield('content')
</main>

<script>
    function openSidebar(){
        document.getElementById('sidebar').classList.add('open');
        document.getElementById('sidebarOverlay').classList.add('show');
    }
    function closeSidebar(){
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
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