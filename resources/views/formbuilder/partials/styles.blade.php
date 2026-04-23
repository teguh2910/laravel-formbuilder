<style>
        :root {
            --primary: #001A72;
            --white: #FFFFFF;
            --light: #F0F2F8;
            --accent: #0038E0;
            --danger: #DC2626;
            --success: #16A34A;
            --gray: #64748B;
            --gray-light: #E2E8F0;
            --gray-dark: #334155;
            --warn: #F59E0B;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; font-family: "Segoe UI", "Helvetica Neue", sans-serif; background: var(--light); color: var(--gray-dark); }
        .hidden { display: none !important; }
        .btn { border: none; border-radius: 8px; padding: 10px 20px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all .2s; }
        .btn:hover { opacity: .9; }
        .btn-primary { background: var(--primary); color: var(--white); }
        .btn-outline { background: transparent; color: var(--primary); border: 2px solid var(--primary); }
        .btn-ghost { background: transparent; color: var(--gray); }
        .card { background: var(--white); border-radius: 12px; box-shadow: 0 1px 3px rgba(0,26,114,.08), 0 4px 12px rgba(0,26,114,.04); padding: 24px; }
        .input { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1.5px solid var(--gray-light); font-size: 14px; outline: none; }
        .label { display: block; font-size: 13px; font-weight: 600; color: var(--gray-dark); margin-bottom: 6px; }
        .landing-wrap { min-height: 100vh; display: flex; flex-direction: column; }
        .landing-nav { background: var(--primary); padding: 0 40px; height: 64px; display: flex; align-items: center; justify-content: space-between; }
        .brand { display: flex; align-items: center; gap: 12px; }
        .brand-logo { width: 36px; height: 36px; border-radius: 8px; background: rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; color: var(--white); font-weight: 700; }
        .brand-text { color: var(--white); font-size: 20px; font-weight: 700; }
        .landing-main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px; background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%); }
        .landing-inner { text-align: center; max-width: 760px; }
        .landing-title { font-size: 48px; font-weight: 800; color: var(--white); margin: 0 0 16px; line-height: 1.15; }
        .landing-sub { font-size: 18px; color: rgba(255,255,255,.75); margin: 0 0 40px; line-height: 1.6; }
        .landing-actions { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
        .feature-grid { margin-top: 48px; display: flex; gap: 24px; justify-content: center; flex-wrap: wrap; }
        .feature { padding: 16px 24px; background: rgba(255,255,255,.08); border-radius: 12px; backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.1); }
        .feature-title { color: var(--white); font-weight: 700; font-size: 15px; }
        .feature-sub { color: rgba(255,255,255,.6); font-size: 13px; margin-top: 4px; }
        .login-wrap, .center-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--primary), var(--accent)); padding: 20px; }
        .login-card { width: 420px; max-width: 94vw; }
        .login-head { text-align: center; margin-bottom: 32px; }
        .login-icon { width: 56px; height: 56px; border-radius: 14px; background: rgba(0,26,114,.1); display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; color: var(--primary); font-size: 22px; font-weight: 700; }
        .login-title { margin: 0 0 4px; color: var(--primary); font-size: 24px; }
        .login-sub { margin: 0; color: var(--gray); font-size: 14px; }
        .mb-18 { margin-bottom: 18px; }
        .mb-24 { margin-bottom: 24px; }
        .toast { position: fixed; bottom: 24px; right: 24px; z-index: 9999; padding: 14px 24px; border-radius: 10px; color: var(--white); font-size: 14px; font-weight: 600; box-shadow: 0 8px 24px rgba(0,0,0,.15); }
        .toast.success { background: var(--success); }
        .toast.error { background: var(--danger); }
        .demo-box { margin-top: 24px; padding: 16px; background: var(--light); border-radius: 8px; font-size: 12px; color: var(--gray); }
        .page-wrap { min-height: 100vh; background: var(--light); }
        .topbar { background: var(--primary); color: var(--white); padding: 0 24px; height: 56px; display: flex; align-items: center; gap: 12px; }
        .container { max-width: 980px; margin: 24px auto; padding: 0 16px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; }
        .muted { color: var(--gray); font-size: 13px; }
        .badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .status-approved { color: var(--success); background: #DCFCE7; }
        .status-in-review { color: var(--accent); background: #DBEAFE; }
        .status-pending { color: var(--warn); background: #FEF3C7; }
        .status-rejected { color: var(--danger); background: #FEE2E2; }
        .field-box { margin-bottom: 14px; }
        .admin-wrap { min-height: 100vh; display: flex; }
        .admin-sidebar { width: 250px; background: var(--primary); color: var(--white); display: flex; flex-direction: column; }
        .admin-main { flex: 1; padding: 24px; overflow: auto; }
        .my-layout { min-height: 100vh; display: flex; }
        .my-sidebar { width: 250px; background: var(--primary); color: var(--white); display: flex; flex-direction: column; }
        .my-main { flex: 1; overflow: auto; }
        .my-main .my-container { max-width: none; margin: 24px; padding: 0; }
        .admin-nav-btn { width: 100%; text-align: left; border: none; border-radius: 8px; padding: 10px 12px; cursor: pointer; background: transparent; color: rgba(255,255,255,.75); font-weight: 600; }
        .admin-nav-btn.active { background: rgba(255,255,255,.15); color: #fff; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 14px; }
        .stat-card { background: var(--white); border-radius: 10px; padding: 14px; box-shadow: 0 1px 3px rgba(0,26,114,.08); }
        .stat-num { font-size: 26px; font-weight: 700; color: var(--primary); margin-bottom: 4px; }
        .editor-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .editor-section { margin-bottom: 16px; }
        .field-row { border: 1px solid var(--gray-light); border-radius: 8px; padding: 12px; margin-bottom: 10px; background: #fff; }
        .chip { display: inline-flex; align-items: center; padding: 3px 8px; border-radius: 999px; font-size: 11px; background: var(--light); color: var(--gray-dark); }
        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, .45); display: flex; align-items: center; justify-content: center; padding: 20px; z-index: 1000; }
        .table-config-wrap { margin-top: 12px; padding: 12px; border: 1px solid var(--gray-light); border-radius: 10px; background: #F8FAFC; }
        .table-config-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 8px; }
        .table-config-grid { width: 100%; border-collapse: collapse; font-size: 12px; background: #fff; border: 1px solid var(--gray-light); border-radius: 8px; overflow: hidden; }
        .table-config-grid th, .table-config-grid td { padding: 8px; border-bottom: 1px solid var(--gray-light); text-align: left; vertical-align: middle; }
        .table-config-grid thead th { background: #EEF2FF; color: var(--primary); font-weight: 700; }
        .table-config-grid tbody tr:last-child td { border-bottom: none; }
        .table-config-grid .cell-small { width: 56px; color: var(--gray); }
        .table-config-grid .cell-actions { width: 96px; text-align: right; }
        .btn-xs { border: none; border-radius: 6px; padding: 5px 8px; font-size: 12px; cursor: pointer; }
        .btn-xs-danger { background: #FEE2E2; color: var(--danger); }
        .btn-xs-primary { background: #DBEAFE; color: var(--accent); }
        @media (max-width: 920px) {
            .my-layout { flex-direction: column; }
            .my-sidebar { width: 100%; }
            .my-main .my-container { margin: 16px; }
        }
    </style>
