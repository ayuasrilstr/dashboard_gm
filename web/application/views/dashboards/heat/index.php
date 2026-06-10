<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($title) ?></title>
    <style>
        :root{
            --bg:#f4f7fb;
            --surface:#ffffff;
            --surface-soft:#f8fafc;
            --ink:#102033;
            --muted:#607287;
            --line:#dbe4ee;
            --grid:#edf2f7;
            --brand:#176b87;
            --brand-2:#64a6bd;
            --accent:#7c9a42;
            --ok:#16a34a;
            --warn:#d97706;
            --risk:#dc2626;
            --shadow:0 10px 26px rgba(16,32,51,.07);
            --tv-safe-x:12px;
            --tv-safe-bottom:18px;
            --tv-scale:.92;
        }
        *{box-sizing:border-box}
        body{margin:0;height:100vh;overflow:auto;background:radial-gradient(circle at top left,rgba(100,166,189,.22),transparent 32%),linear-gradient(180deg,#f8fbff 0,var(--bg) 100%);color:var(--ink);font-family:Segoe UI,Arial,Helvetica,sans-serif}
        .page{height:calc(100vh / var(--tv-scale));width:calc(100vw / var(--tv-scale));padding:6px var(--tv-safe-x) var(--tv-safe-bottom);display:grid;grid-template-rows:38px minmax(0,1fr);gap:6px;transform:scale(var(--tv-scale));transform-origin:top left}
        .top{display:grid;grid-template-columns:minmax(520px,34vw) auto 1fr;align-items:center;gap:10px}
        .title{height:34px;border:1px solid rgba(219,228,238,.9);border-radius:7px;background:rgba(255,255,255,.92);box-shadow:var(--shadow);display:flex;align-items:center;justify-content:center;font-size:clamp(18px,1.22vw,24px);font-weight:850;letter-spacing:.3px;color:var(--ink);white-space:nowrap;overflow:hidden}
        .menu{height:34px;border:1px solid rgba(219,228,238,.9);border-radius:7px;background:rgba(255,255,255,.78);box-shadow:var(--shadow);display:flex;align-items:center;padding:3px;gap:4px}
        .menu button{height:27px;border:0;border-radius:5px;background:transparent;color:var(--muted);font-size:clamp(11px,.8vw,14px);font-weight:800;text-transform:uppercase;padding:0 12px;cursor:pointer}
        .menu button.active{background:var(--brand);color:#fff;box-shadow:0 8px 18px rgba(23,107,135,.22)}
        .delivery-toggle{height:34px;border:1px solid rgba(219,228,238,.9);border-radius:7px;background:rgba(255,255,255,.78);box-shadow:var(--shadow);display:flex;align-items:center;padding:3px;gap:4px}
        .delivery-toggle button{height:27px;border:0;border-radius:5px;background:transparent;color:var(--muted);font-size:clamp(11px,.8vw,14px);font-weight:800;text-transform:uppercase;padding:0 10px;cursor:pointer;white-space:nowrap}
        .delivery-toggle button.active{background:var(--accent);color:#fff;box-shadow:0 8px 18px rgba(124,154,66,.2)}
        .workday-open{height:34px;border:1px solid rgba(219,228,238,.9);border-radius:7px;background:#fff;color:var(--brand);font-size:clamp(11px,.8vw,14px);font-weight:850;text-transform:uppercase;box-shadow:var(--shadow);padding:0 14px;cursor:pointer}
        .last-update{justify-self:end;position:relative}.last-update button{height:34px;border:1px solid rgba(219,228,238,.9);border-radius:999px;background:rgba(255,255,255,.82);color:var(--muted);font-size:clamp(12px,.84vw,15px);font-weight:750;box-shadow:var(--shadow);padding:0 13px;cursor:pointer}.last-update-panel{position:fixed;right:var(--tv-safe-x);top:50px;z-index:60;width:min(420px,calc(100vw - (var(--tv-safe-x) * 2)));max-height:calc(100vh - 112px);overflow:auto;display:none;border:1px solid var(--line);border-radius:8px;background:#fff;box-shadow:0 18px 44px rgba(16,32,51,.18);padding:10px}.last-update.open .last-update-panel{display:block}.last-update-panel h3{margin:0 0 8px;color:var(--ink);font-size:12px;font-weight:900;text-transform:uppercase}.db-last-list{display:grid;gap:7px;max-height:calc(100vh - 166px);overflow:auto}.db-last-item{border:1px solid var(--grid);border-radius:8px;background:#fff;padding:8px}.db-last-item b{display:block;color:var(--ink);font-size:12px;font-weight:900;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.db-last-item p{margin:3px 0 0;color:var(--muted);font-size:12px;line-height:1.3}.db-last-empty{padding:12px;border:1px dashed var(--line);border-radius:8px;color:var(--muted);font-size:12px;font-weight:750;text-align:center}
        .dashboard{min-height:0;display:grid;grid-template-columns:56% 44%;gap:8px}
        .view{min-height:0;display:none}.view.active{display:grid}
        .left{min-height:0;display:grid;grid-template-rows:42% 58%;gap:8px}
        .top-charts{min-height:0;display:grid;grid-template-columns:1.05fr .95fr;gap:8px}
        .right{min-height:0;display:grid;grid-template-rows:128px minmax(0,1fr);gap:8px}
        .analytics-view{align-content:start;grid-template-rows:72px 132px minmax(0,auto);gap:10px}
        .analytics-detail{min-height:0;display:grid;grid-template-columns:1fr 1fr 1.18fr;gap:10px;align-items:start}
        .kpis{display:grid;grid-template-columns:1fr 1fr 1.02fr;gap:8px}
        .box{border:1px solid rgba(219,228,238,.95);border-radius:8px;background:rgba(255,255,255,.94);box-shadow:var(--shadow);min-height:0;overflow:hidden}
        .chart-box{padding:11px 13px 10px;display:flex;flex-direction:column}
        #qtyPdkOutputChart,#readyToLoadChart,#outputCapacityChart{min-height:0;flex:1;display:flex;flex-direction:column}
        .chart-title{margin:0 0 5px;text-align:left;color:var(--ink);font-size:clamp(14px,.98vw,18px);font-weight:850;text-transform:uppercase;letter-spacing:.01em}
        .chart-area{min-height:0;flex:1;display:flex;align-items:end;justify-content:space-around;gap:clamp(18px,1.7vw,34px);border:1px solid var(--grid);border-radius:7px;padding:28px 12px 0;background:linear-gradient(180deg,#fff,var(--surface-soft))}
        .group{flex:0 1 clamp(92px,6.2vw,128px);min-width:0;height:100%;display:grid;grid-template-rows:1fr clamp(20px,1.24vw,25px);gap:2px}
        .bars{height:100%;display:flex;align-items:end;justify-content:center;gap:clamp(4px,.34vw,7px)}
        .vbar{width:clamp(32px,2.55vw,50px);min-height:2px;background:linear-gradient(180deg,var(--brand),#0f5268);border-radius:6px 6px 2px 2px;position:relative;box-shadow:0 8px 17px rgba(23,107,135,.17)}
        .vbar.alt{background:linear-gradient(180deg,var(--accent),#58712f)}
        .vbar.third{background:linear-gradient(180deg,#f59e0b,#b45309)}
        .vbar small{position:absolute;left:50%;bottom:calc(100% + 3px);transform:translateX(-50%);background:#17202d;color:#fff;border-radius:5px;padding:2px 5px;font-size:clamp(10px,.74vw,14px);font-weight:800;white-space:nowrap}
        .vbar .label-0{transform:translateX(-74%)}.vbar .label-1{transform:translateX(-26%)}.vbar .label-2{transform:translateX(-14%)}
        .vbar.alt small{background:#43591f;color:#fff}.vbar.third small{background:#92400e;color:#fff}
        .glabel{text-align:center;font-size:clamp(11px,.78vw,14px);color:var(--muted);white-space:nowrap;font-weight:700}
        .legend{display:flex;justify-content:flex-end;gap:10px;margin-top:4px;font-size:clamp(10px,.74vw,14px);color:var(--muted);font-weight:700}
        .legend span{display:inline-flex;align-items:center;gap:5px}.dot{width:clamp(8px,.58vw,11px);height:clamp(8px,.58vw,11px);border-radius:999px;display:inline-block;background:var(--brand)}.dot.alt{background:var(--accent)}.dot.output{background:var(--accent)}.line-key{width:clamp(16px,1.2vw,24px);height:0;border-top:3px solid #f59e0b;display:inline-block}
        .ready .chart-area{gap:clamp(20px,1.8vw,36px);padding-left:clamp(20px,2vw,36px);padding-right:clamp(20px,2vw,36px)}.ready .vbar{width:clamp(42px,3.25vw,64px);background:linear-gradient(180deg,var(--brand),#0f5268)}.ready .vbar small{background:#17202d;color:#fff}
        .capacity{padding:11px 13px 10px}
        .capacity .chart-area{position:relative;z-index:1;min-height:0;flex:1;border:1px solid var(--grid);border-radius:7px;background:linear-gradient(180deg,#fff,var(--surface-soft));padding:0}
        .capacity .group{position:relative;z-index:2}
        .capacity .bars{position:relative}
        .capacity .vbar{width:clamp(34px,2.45vw,48px);background:linear-gradient(180deg,var(--brand),#0f5268)}
        .capacity .vbar.alt{background:linear-gradient(180deg,#9fc06a,#6d8737)}
        .capacity .vbar small{background:#17202d;color:#fff}
        .capacity .vbar.alt small{background:#43591f;color:#fff}
        .capacity .input-line-chart{position:absolute;z-index:4;overflow:visible;pointer-events:none}
        .capacity .input-line-chart polyline{fill:none;stroke:#f59e0b;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;vector-effect:non-scaling-stroke}
        .capacity .input-line-labels{position:absolute;z-index:6;pointer-events:none}
        .capacity .input-value{position:absolute;transform:translateX(-50%);background:#f59e0b;color:#fff;border-radius:5px;padding:2px 5px;font-size:clamp(9px,.68vw,12px);font-weight:900;line-height:1;white-space:nowrap;box-shadow:0 0 0 2px rgba(255,255,255,.72)}
        .capacity .chart-gridline{position:absolute;border-top:1px solid var(--grid);height:0;pointer-events:none;z-index:1}
        .capacity .chart-y-label{position:absolute;color:var(--muted);font-size:clamp(9px,.65vw,11px);font-weight:800;pointer-events:none;z-index:1;line-height:1}
        #outputCapacityChart{flex-direction:row;gap:15px}
        .chart-sidebar{width:115px;flex-shrink:0;border-right:1px solid var(--grid);padding:5px 12px 5px 0;display:flex;flex-direction:column;gap:12px}
        .sidebar-title{font-size:clamp(11px,.8vw,14px);font-weight:900;color:var(--ink);letter-spacing:.05em;margin-bottom:2px}
        .sidebar-item{display:flex;flex-direction:column;gap:3px}
        .sidebar-label{display:inline-flex;align-items:center;gap:6px;font-size:clamp(9px,.68vw,11px);font-weight:800;color:var(--muted)}
        .sidebar-label .dot{width:8px;height:8px;margin:0}
        .sidebar-label .line-key{width:14px;height:0;border-top:3px solid #f59e0b}
        .sidebar-value{font-size:clamp(16px,1.2vw,22px);font-weight:900;color:var(--ink);padding-left:14px}
        .kpi{background:linear-gradient(180deg,#fff,#f6fafc);border:1px solid rgba(219,228,238,.95);border-radius:7px;padding:12px;color:var(--ink);box-shadow:var(--shadow)}
        .kpi span{display:block;font-size:clamp(12px,.82vw,15px);font-weight:850;text-transform:uppercase;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.kpi strong{display:block;margin-top:7px;font-size:clamp(26px,1.92vw,36px);line-height:1;font-weight:900;color:var(--ink)}.kpi small{font-size:clamp(14px,1vw,19px);font-weight:850;color:var(--muted)}
        .kpi.balance-detail{display:grid;grid-template-rows:auto minmax(0,1fr);gap:5px}.kpi-balance-list{min-height:0;display:grid;gap:2px;align-content:start}.kpi-balance-row{display:grid;grid-template-columns:minmax(70px,1fr) 8px minmax(54px,.8fr) 26px;gap:5px;align-items:center;color:var(--ink);font-size:clamp(12px,.84vw,16px);font-weight:750;line-height:1.08}.kpi-balance-row b{text-align:right;font-size:inherit}.kpi-balance-row small{color:var(--muted);font-size:clamp(10px,.7vw,13px);font-weight:750}
        .condition-card{padding:11px 16px;display:grid;grid-template-columns:182px 132px minmax(0,1fr) 110px;align-items:center;gap:14px;border-left:6px solid var(--brand)}.condition-card h2{margin:0;color:var(--muted);font-size:clamp(11px,.76vw,13px);font-weight:850;text-transform:uppercase;letter-spacing:.04em}.condition-level{display:inline-flex;align-items:center;justify-content:center;height:38px;border-radius:999px;font-size:clamp(17px,1.18vw,23px);font-weight:950;text-transform:uppercase}.condition-text{min-width:0;color:var(--ink);font-size:clamp(13px,.92vw,17px);font-weight:800;line-height:1.25}.condition-meta{justify-self:end;color:var(--muted);font-size:clamp(10px,.7vw,13px);font-weight:850;text-transform:uppercase;text-align:right}.condition-shortage{grid-column:4;justify-self:end;color:var(--muted);font-size:clamp(10px,.72vw,13px);font-weight:850;text-transform:uppercase;white-space:nowrap;text-align:right}.condition-shortage b{color:var(--ink);font-size:clamp(13px,.92vw,18px);font-weight:950}.condition-card.good{border-color:#bbf7d0;border-left-color:var(--ok);background:linear-gradient(90deg,#f4fff7 0,#fff 62%)}.condition-card.good .condition-level{background:#dcfce7;color:#166534}.condition-card.watch{border-color:#fde68a;border-left-color:var(--warn);background:linear-gradient(90deg,#fffbea 0,#fff 62%)}.condition-card.watch .condition-level{background:#fef3c7;color:#92400e}.condition-card.risk{border-color:#fecaca;border-left-color:var(--risk);background:linear-gradient(90deg,#fff5f5 0,#fff 62%)}.condition-card.risk .condition-level{background:#fee2e2;color:#991b1b}
        .condition-card.simple{grid-template-columns:220px 150px minmax(0,1fr) 130px}.condition-card.simple .condition-text{display:none}.condition-card.simple .condition-meta{display:none}
        .analytics{padding:11px 14px;display:grid;grid-template-rows:auto minmax(0,1fr);gap:9px}.analytics h2,.summary h2,.priority h2{margin:0;color:var(--ink);font-size:clamp(13px,.92vw,17px);font-weight:850;text-transform:uppercase;letter-spacing:.02em}
        .metric-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.metric{border:1px solid var(--line);border-radius:8px;background:#fff;padding:10px 72px 10px 12px;min-width:0;box-shadow:0 8px 20px rgba(16,32,51,.04);position:relative;overflow:visible}.metric span{display:block;color:var(--muted);font-size:clamp(9px,.64vw,12px);font-weight:850;text-transform:uppercase;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.metric strong{display:block;margin-top:6px;color:var(--ink);font-size:clamp(17px,1.36vw,26px);line-height:1;font-weight:900}.metric small{font-size:clamp(10px,.7vw,13px);color:var(--muted);font-weight:800}.metric.good{border-color:#bbf7d0;background:#f8fff9}.metric.watch{border-color:#fde68a;background:#fffdf1}.metric.risk{border-color:#fecaca;background:#fff8f8}.detail-btn{position:absolute;right:10px;bottom:9px;height:24px;border:1px solid var(--line);border-radius:6px;background:#fff;color:var(--brand);font-size:10px;font-weight:850;text-transform:uppercase;padding:0 9px;cursor:pointer}.detail-btn:hover{border-color:var(--brand);background:#eef7fa}.summary-item{padding-right:72px}.summary-item .detail-btn{height:22px;right:9px;bottom:8px;font-size:9px;padding:0 8px}
        .insights{min-height:0;overflow:hidden;display:grid;grid-template-columns:1fr;gap:7px;align-content:start}.insight{display:grid;grid-template-columns:9px 1fr;gap:9px;align-items:start;min-width:0;border:1px solid var(--grid);border-radius:8px;background:#fff;padding:8px 9px}.badge{width:9px;height:9px;border-radius:999px;margin-top:5px;background:var(--brand)}.insight.good .badge,.accuracy-row.good .badge,.action-row.good .badge{background:var(--ok)}.insight.watch .badge,.accuracy-row.watch .badge,.action-row.watch .badge{background:var(--warn)}.insight.risk .badge,.accuracy-row.risk .badge,.action-row.risk .badge{background:var(--risk)}.insight b{display:block;color:var(--ink);font-size:clamp(12px,.82vw,14px);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.insight p{margin:2px 0 0;color:var(--muted);font-size:clamp(10px,.72vw,13px);line-height:1.28}
        .summary{padding:11px 14px;display:grid;grid-template-rows:auto auto auto auto;gap:9px}.summary-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;align-content:start}.summary-item{border:1px solid var(--grid);border-radius:8px;background:#fff;padding:8px 72px 8px 10px;position:relative;overflow:visible}.summary-item span{display:block;color:var(--muted);font-size:clamp(9px,.64vw,12px);font-weight:850;text-transform:uppercase;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.summary-item strong{display:block;margin-top:4px;color:var(--ink);font-size:clamp(15px,1vw,20px);font-weight:900}.accuracy-list{min-height:0;overflow:visible;display:grid;gap:7px;align-content:start}.accuracy-row{border:1px solid var(--grid);border-radius:8px;background:#fff;padding:8px;display:grid;grid-template-columns:9px 1fr;gap:9px;position:relative;overflow:visible}.accuracy-row b{display:block;color:var(--ink);font-size:clamp(11px,.74vw,13px);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.accuracy-row p{margin:2px 0 0;color:var(--muted);font-size:clamp(10px,.68vw,12px);line-height:1.24}.section-label{margin:0;color:var(--muted);font-size:clamp(10px,.68vw,13px);font-weight:850;text-transform:uppercase}.action-card{padding:11px 14px;display:grid;grid-template-rows:auto minmax(0,1fr);gap:9px}.action-card h2{margin:0;color:var(--ink);font-size:clamp(13px,.92vw,17px);font-weight:850;text-transform:uppercase;letter-spacing:.02em}.action-list{min-height:0;overflow:visible;display:grid;gap:8px;align-content:start}.action-row{display:grid;grid-template-columns:9px 1fr;gap:9px;border:1px solid var(--grid);border-radius:8px;background:#fff;padding:8px 9px;position:relative;overflow:visible}.action-row b{display:block;color:var(--ink);font-size:clamp(11px,.76vw,14px);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.action-row p{margin:3px 0 0;color:var(--muted);font-size:clamp(10px,.68vw,12px);line-height:1.24}.action-row p strong{color:var(--ink);font-weight:850}.action-row .cap-row{display:grid;grid-template-columns:88px 1fr;gap:4px;margin:2px 0 0;align-items:baseline}.action-row .cap-label{font-size:clamp(9px,.64vw,11px);font-weight:900;text-transform:uppercase;color:var(--brand);white-space:nowrap}.action-row .cap-label.penyebab{color:var(--warn)}.action-row .cap-label.pencegahan{color:var(--ok)}.action-row .cap-label.penanganan{color:var(--risk)}.action-row .cap-label.masalah{color:var(--muted)}.action-row .cap-val{font-size:clamp(10px,.68vw,12px);color:var(--ink);line-height:1.26}.hover-detail{display:none}
        .priority{padding:11px 13px}.priority h2{margin-bottom:7px}.table-scroll{height:calc(100% - 27px);overflow:hidden;border:1px solid var(--grid);border-radius:7px;background:#fff}table{width:100%;border-collapse:collapse;color:var(--ink);font-size:clamp(14px,.9vw,16px)}th{height:32px;color:var(--muted);font-size:clamp(11px,.76vw,14px);text-align:left;text-transform:uppercase;background:var(--surface-soft);font-weight:850}td{border-bottom:1px solid var(--grid);vertical-align:middle}tbody tr:nth-child(even){background:#fafcff}th:first-child,td:first-child{width:32px;text-align:right;padding-right:8px}.order{width:142px;white-space:nowrap;font-size:clamp(12px,.82vw,15px)}.style{width:150px}.delivery{text-align:center}.num{text-align:right;font-weight:850}.unit{padding-left:5px;font-size:clamp(11px,.76vw,14px);color:var(--muted);font-weight:700}.priority table{height:100%}.priority tbody tr{height:10%}
        .modal-backdrop{position:fixed;left:0;top:0;width:100vw;height:100vh;z-index:9999;display:none;align-items:flex-start;justify-content:center;padding:58px 18px 28px;background:rgba(16,32,51,.42)}.modal-backdrop.open{display:flex}.detail-modal{width:min(1080px,94vw);height:auto;max-height:calc(100vh - 96px);border:1px solid var(--line);border-radius:8px;background:#fff;box-shadow:0 28px 70px rgba(16,32,51,.28);display:grid;grid-template-rows:52px minmax(0,1fr)}.modal-head{height:52px;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:0 16px;border-bottom:1px solid var(--grid)}.modal-head h3{margin:0;color:var(--ink);font-size:16px;font-weight:900;text-transform:uppercase}.modal-close{width:32px;height:32px;border:1px solid var(--line);border-radius:6px;background:#fff;color:var(--ink);font-size:22px;line-height:1;cursor:pointer}.modal-body{min-height:0;max-height:calc(100vh - 148px);padding:14px;overflow:auto}.calc-note{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px}.calc-item{border:1px solid var(--grid);border-radius:8px;background:var(--surface-soft);padding:9px 10px;text-align:center}.calc-item span{display:block;color:var(--muted);font-size:11px;font-weight:850;text-transform:uppercase}.calc-item strong{display:block;margin-top:4px;color:var(--ink);font-size:15px;font-weight:900}.calc-formula{grid-column:1/-1;border:1px solid var(--line);border-radius:8px;background:#fff;padding:9px 10px;color:var(--muted);font-size:12px;font-weight:750;text-align:center}.detail-table{border:1px solid var(--grid);border-radius:8px;overflow:auto}.detail-table table{font-size:13px}.detail-table th,.detail-table td{padding:8px 10px;text-align:center}.detail-table th:first-child,.detail-table td:first-child{width:auto;text-align:center;padding-right:10px}.detail-table .num{text-align:center}.detail-empty{padding:18px;border:1px dashed var(--line);border-radius:8px;color:var(--muted);font-weight:750;text-align:center}
        .workday-modal{width:min(980px,94vw)}.calendar-tools{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}.calendar-tools button{height:32px;border:1px solid var(--line);border-radius:6px;background:#fff;color:var(--ink);font-weight:850;padding:0 12px;cursor:pointer}.calendar-month-title{color:var(--ink);font-size:16px;font-weight:900;text-transform:uppercase}.calendar-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-bottom:10px}.calendar-summary-item{border:1px solid var(--grid);border-radius:7px;background:var(--surface-soft);padding:7px 8px}.calendar-summary-item span{display:block;color:var(--muted);font-size:10px;font-weight:850;text-transform:uppercase}.calendar-summary-item b{display:block;margin-top:3px;color:var(--ink);font-size:15px;font-weight:900}.calendar-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:5px}.calendar-head{padding:6px 4px;color:var(--muted);font-size:11px;font-weight:900;text-align:center;text-transform:uppercase}.calendar-day{min-height:62px;border:1px solid var(--grid);border-radius:7px;background:#fff;color:var(--ink);display:grid;grid-template-rows:auto 1fr;align-items:start;padding:6px;text-align:left;cursor:pointer}.calendar-day b{font-size:13px}.calendar-day span{align-self:end;color:var(--muted);font-size:10px;font-weight:800;text-transform:uppercase}.calendar-day.out{opacity:.38}.calendar-day.sunday{background:#fff7f7}.calendar-day.work{border-color:#bbf7d0;background:#f0fdf4;color:#166534}.calendar-day.half{border-color:#fed7aa;background:#fff7ed;color:#9a3412}.calendar-day.holiday{border-color:#fecaca;background:#fee2e2;color:#991b1b}.calendar-day.today{box-shadow:inset 0 0 0 2px var(--brand)}.calendar-legend{display:flex;gap:12px;align-items:center;margin-top:10px;color:var(--muted);font-size:12px;font-weight:750}.calendar-legend i{width:11px;height:11px;border-radius:3px;display:inline-block;margin-right:5px;vertical-align:-1px}.legend-work{background:#f0fdf4;border:1px solid #bbf7d0}.legend-half{background:#fff7ed;border:1px solid #fed7aa}.legend-off{background:#fee2e2;border:1px solid #fecaca}.legend-sunday{background:#fff7f7;border:1px solid var(--grid)}.calendar-help{margin-top:8px;color:var(--muted);font-size:12px;font-weight:750}.modal-actions{display:flex;align-items:center;justify-content:flex-end;gap:8px;margin-top:12px}.modal-actions button{height:34px;border:1px solid var(--line);border-radius:6px;background:#fff;color:var(--ink);font-weight:850;padding:0 14px;cursor:pointer}.modal-actions .primary{border-color:var(--brand);background:var(--brand);color:#fff}.workday-message{margin-right:auto;color:var(--muted);font-size:12px;font-weight:750}
        @media (max-height: 820px){
            .page{padding:8px var(--tv-safe-x) var(--tv-safe-bottom);grid-template-rows:40px minmax(0,1fr);gap:8px}
            .top{gap:8px}.title,.menu{height:38px}.menu button{height:30px;padding:0 12px}
            .last-update button{height:36px;font-size:12px}.last-update-panel{position:fixed;right:var(--tv-safe-x);top:50px;width:min(420px,calc(100vw - (var(--tv-safe-x) * 2)));max-height:calc(100vh - 96px);overflow:auto}.db-last-list{max-height:calc(100vh - 150px)}
            .dashboard{gap:8px}.left{gap:8px}.top-charts{gap:8px}.right{grid-template-rows:132px minmax(0,1fr);gap:8px}
            .chart-box,.capacity,.priority{padding:9px 11px}.chart-title{margin-bottom:5px}.chart-area{padding-top:23px}.legend{margin-top:4px}
            .kpi{padding:9px 10px}.kpi strong{margin-top:6px;font-size:clamp(21px,1.65vw,30px)}.kpi.balance-detail{gap:5px}.kpi-balance-row{font-size:clamp(11px,.76vw,14px);grid-template-columns:minmax(64px,1fr) 9px minmax(46px,.75fr) 24px;gap:5px}
            .priority h2{margin-bottom:5px}.table-scroll{height:calc(100% - 24px);overflow:auto}.priority tbody tr{height:auto}
            .analytics-view{grid-template-rows:58px 112px minmax(0,1fr);gap:8px}.analytics-detail{height:calc(100vh - 226px);gap:8px}.analytics,.summary,.action-card{padding:9px 11px}
            .condition-card{padding:8px 12px;grid-template-columns:150px 116px minmax(0,1fr) 90px;gap:10px}.condition-card.simple{grid-template-columns:180px 116px minmax(0,1fr) 104px}.condition-level{height:32px}
            .metric-grid{gap:8px}.metric{padding:8px 66px 8px 10px}.metric strong{margin-top:4px;font-size:clamp(16px,1.18vw,22px)}.detail-btn{height:21px;right:8px;bottom:8px}
            .summary-grid{gap:7px}.summary-item{padding:7px 66px 7px 9px}.summary-item strong{font-size:clamp(14px,.9vw,18px)}
            .insights,.accuracy-list,.action-list{overflow:auto;align-content:start}.insight,.accuracy-row,.action-row{padding:7px 8px}.action-row p,.accuracy-row p,.insight p{line-height:1.18}
            .modal-backdrop{align-items:flex-start;padding:54px 16px 16px}.detail-modal{max-height:calc(100vh - 70px);width:min(1040px,96vw)}.modal-body{max-height:calc(100vh - 122px);padding:10px}.calc-note{gap:6px;margin-bottom:8px}.calc-item{padding:7px 8px}.detail-table th,.detail-table td{padding:6px 8px}
        }
        @media (hover: none) and (pointer: coarse){
            :root{--tv-safe-x:24px;--tv-safe-bottom:40px}
            .chart-title,.analytics h2,.summary h2,.priority h2,.action-card h2{font-size:clamp(12px,.82vw,16px)}
            .kpi strong{font-size:clamp(21px,1.65vw,32px)}
            table{font-size:clamp(12px,.78vw,15px)}
            .action-row p,.accuracy-row p,.insight p{font-size:clamp(9px,.62vw,11px)}
        }
        .login-modal{width:min(360px,92vw)}.login-fields{display:grid;gap:10px}.login-fields label{display:grid;gap:5px;color:var(--muted);font-size:12px;font-weight:850;text-transform:uppercase}.login-fields input{height:38px;border:1px solid var(--line);border-radius:7px;padding:0 10px;color:var(--ink);font-size:14px;font-weight:750;outline:none}.login-fields input:focus{border-color:var(--brand);box-shadow:0 0 0 2px rgba(11,111,150,.14)}.login-message{margin-right:auto;color:var(--risk);font-size:12px;font-weight:750}
        .calendar-day.quarter{border-color:#bfdbfe;background:#eff6ff;color:#1d4ed8}.legend-quarter{background:#eff6ff;border:1px solid #bfdbfe}
        .public-analytics.analytics-view{grid-template-rows:72px minmax(132px,auto) minmax(0,1fr)}
        .public-analytics .condition-card.simple{grid-template-columns:220px 150px minmax(0,1fr)}
        .public-analytics .condition-shortage,.public-analytics .summary,.public-analytics .action-card{display:none}
        .public-analytics .metric-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
        .public-analytics .metric{padding-right:72px}
        .public-analytics .analytics-detail{grid-template-columns:1fr}
        .public-analytics.show-actions .analytics-detail{grid-template-columns:1fr 1.18fr}
        .public-analytics.show-actions .action-card{display:grid}
        .analytics-secret{position:fixed;right:7px;bottom:7px;z-index:45;width:18px;height:18px;border:0;border-radius:999px;background:rgba(96,114,135,.08);opacity:.14;cursor:default}
        .analytics-secret:hover{opacity:.28}
        .analytics-menu{width:min(620px,94vw)}
        .analytics-card-options{display:grid;grid-template-columns:1fr 1fr;gap:8px;max-height:min(56vh,430px);overflow:auto}
        .analytics-card-option{border:1px solid var(--grid);border-radius:8px;background:#fff;padding:9px 10px;display:grid;grid-template-columns:18px 1fr;gap:8px;align-items:start;color:var(--ink);font-size:13px;font-weight:850}
        .analytics-card-option input{margin-top:1px}
        .analytics-card-option span{display:block;color:var(--muted);font-size:11px;font-weight:750;line-height:1.25;margin-top:2px}
        .analytics-head{display:flex;align-items:center;justify-content:space-between;gap:8px}
        .analytics-head-tools{display:flex;align-items:center;gap:6px}
        .analytics-lang{height:18px;border:1px solid rgba(96,114,135,.18);border-radius:5px;background:rgba(255,255,255,.75);display:flex;overflow:hidden}
        .analytics-lang button{width:28px;border:0;background:transparent;color:rgba(96,114,135,.68);font-size:10px;font-weight:900;cursor:pointer}
        .analytics-lang button.active{background:rgba(23,107,135,.14);color:var(--brand)}
        .analytics-tune{width:22px;height:18px;border:0;border-radius:5px;background:rgba(96,114,135,.12);color:rgba(96,114,135,.62);font-size:12px;font-weight:900;line-height:1;cursor:pointer}
        .analytics-tune:hover{background:rgba(23,107,135,.16);color:var(--brand)}
        .management-modal{width:min(1020px,94vw)}.management-body{display:grid;gap:12px}.management-note{margin:0;color:var(--muted);font-size:12px;font-weight:750;line-height:1.4}.management-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.management-card{border:1px solid var(--grid);border-radius:10px;background:linear-gradient(180deg,#fff,#f8fbfd);padding:12px;display:grid;gap:10px;box-shadow:0 10px 20px rgba(16,32,51,.04)}.management-card-head{display:flex;align-items:center;justify-content:space-between;gap:10px}.management-card-head b{color:var(--ink);font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:.02em}.management-card-head span{color:var(--muted);font-size:11px;font-weight:850;text-transform:uppercase}.management-card p{margin:0;color:var(--muted);font-size:12px;line-height:1.35;font-weight:700}.management-delivery-toggle{display:flex;gap:6px;flex-wrap:wrap}.management-delivery-toggle button{height:32px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--muted);font-size:12px;font-weight:850;padding:0 12px;cursor:pointer}.management-delivery-toggle button.active{background:var(--accent);border-color:var(--accent);color:#fff;box-shadow:0 8px 18px rgba(124,154,66,.18)}.management-card .primary{height:34px;border:1px solid var(--brand);border-radius:8px;background:var(--brand);color:#fff;font-weight:850;cursor:pointer;padding:0 14px;justify-self:start}
    </style>
</head>
<body>
<div class="page">
    <header class="top">
        <div class="title" data-i18n="app_title">DASHBOARD HEAT TRANSFER</div>
        <nav class="menu" aria-label="Menu dashboard">
            <button type="button" class="active" data-view="dashboard" data-i18n="dashboard">Dashboard</button>
            <button type="button" data-view="analytic" data-i18n="analytic">Analytic</button>
        </nav>
        <div class="last-update" id="lastUpdateBox">
            <button type="button" id="lastUpdate">*Last Update : -</button>
            <div class="last-update-panel" id="lastUpdatePanel">
                <h3 data-i18n="last_data">Data Terakhir</h3>
                <div id="lastUpdateRows"></div>
            </div>
        </div>
    </header>

    <main class="dashboard view active" id="dashboardView">
        <section class="left">
            <div class="top-charts">
                <div class="box chart-box qty">
                    <h2 class="chart-title" data-i18n="qty_pdk_output_chart">QTY PDK vs QTY OUTPUT</h2>
                    <div id="qtyPdkOutputChart"></div>
                    <div class="legend"><span><i class="dot"></i><span data-i18n="qty_pdk">QTY PDK</span></span><span><i class="dot alt"></i><span data-i18n="qty_out">QTY OUT</span></span></div>
                </div>
                <div class="box chart-box ready">
                    <h2 class="chart-title" data-i18n="ready_to_load_chart">READY TO LOAD PRODUCTION</h2>
                    <div id="readyToLoadChart"></div>
                </div>
            </div>
                <div class="box chart-box capacity">
                    <h2 class="chart-title" data-i18n="capacity_output_input_chart">KAPASITAS vs OUTPUT vs INPUT</h2>
                    <div id="outputCapacityChart"></div>
                <div class="legend"><span><i class="dot"></i><span data-i18n="capacity">KAPASITAS</span></span><span><i class="dot output"></i><span data-i18n="output">OUTPUT</span></span><span><i class="line-key"></i><span data-i18n="input">INPUT</span></span></div>
            </div>
        </section>

        <section class="right">
            <div class="kpis">
                <article class="kpi"><span data-i18n="total_output_label">Total Output :</span><strong><b id="totalOutput">-</b> <small data-i18n="pcs">Pcs</small></strong></article>
                <article class="kpi"><span data-i18n="balance_qty_label">Balance Qty :</span><strong><b id="balanceQty">-</b> <small data-i18n="pcs">Pcs</small></strong></article>
                <article class="kpi balance-detail"><span data-i18n="qty_short_label">Qty Yang Kurang :</span><div class="kpi-balance-list" id="balanceBreakdown"></div></article>
            </div>
            <div class="box priority">
                <h2 data-i18n="top_priority_title">Top 10 Priority Orders Ready For Production :</h2>
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>No.</th><th></th><th>Style</th><th data-i18n="delivery_date">Tgl. Delivery</th><th data-i18n="qty_ready">Qty Ready</th><th></th></tr></thead>
                        <tbody id="priorityRows"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <main class="view analytics-view public-analytics" id="analyticView">
        <section class="box condition-card" id="overallCondition">
            <h2 data-i18n="main_condition">Kondisi Utama Area :</h2>
            <div class="condition-level" id="conditionLevel">-</div>
            <div class="condition-text" id="conditionSummary">-</div>
            <div class="condition-shortage" id="conditionShortage"><span data-i18n="shortage">Kurang</span>: <b>-</b> <span data-i18n="pcs">Pcs</span></div>
            <div class="condition-meta" id="conditionMeta" data-i18n="overall_level">Overall Level</div>
        </section>
        <section class="box analytics">
            <h2 class="analytics-head">
                <span id="analyticsTitle">Data-Driven & Management Analytics :</span>
                <span class="analytics-head-tools">
                    <span class="analytics-lang" aria-label="Analytics language">
                        <button type="button" data-analytics-lang="id">ID</button>
                        <button type="button" data-analytics-lang="en">EN</button>
                    </span>
                    <button type="button" class="analytics-tune" id="analyticsTune" aria-label="More options">...</button>
                </span>
            </h2>
            <div class="metric-grid" id="analyticsMetrics"></div>
        </section>
        <section class="analytics-detail">
            <div class="box analytics">
                <h2 id="analyticsInsightTitle">Management Insight :</h2>
                <div class="insights" id="analyticsInsights"></div>
            </div>
            <div class="box summary">
                <h2 data-i18n="management_summary">Management Summary :</h2>
                <div class="summary-grid" id="analyticsSummary"></div>
                <div class="section-label" data-i18n="data_accuracy_issues">Data Accuracy Issues</div>
                <div class="accuracy-list" id="dataAccuracyRows"></div>
            </div>
            <div class="box action-card">
                <h2 data-i18n="prevention_handling">Prevention & Handling :</h2>
                <div class="action-list" id="managementActions"></div>
            </div>
        </section>
    </main>
</div>
<button type="button" class="analytics-secret" id="analyticsSecret" aria-label=""></button>

<div class="modal-backdrop" id="detailModal" role="dialog" aria-modal="true" aria-labelledby="detailModalTitle">
    <section class="detail-modal">
        <div class="modal-head">
            <h3 id="detailModalTitle">Detail</h3>
            <button type="button" class="modal-close" id="detailModalClose" aria-label="Tutup detail">&times;</button>
        </div>
        <div class="modal-body" id="detailModalBody"></div>
    </section>
</div>

<div class="modal-backdrop" id="workdayModal" role="dialog" aria-modal="true" aria-labelledby="workdayModalTitle">
    <section class="detail-modal workday-modal">
        <div class="modal-head">
            <h3 id="workdayModalTitle" data-i18n="production_calendar">Production Calender</h3>
            <button type="button" class="modal-close" id="workdayModalClose" aria-label="Tutup kalender">&times;</button>
        </div>
        <div class="modal-body">
            <div class="calendar-tools">
                <button type="button" id="calendarPrev" data-i18n="previous">Sebelumnya</button>
                <div class="calendar-month-title" id="calendarTitle">-</div>
                <button type="button" id="calendarNext" data-i18n="next">Berikutnya</button>
            </div>
            <div class="calendar-summary" id="calendarSummary"></div>
            <div class="calendar-grid" id="calendarRows"></div>
            <div class="calendar-legend">
                <span><i class="legend-sunday"></i><span data-i18n="sunday_off">Minggu/off</span></span>
                <span><i class="legend-work"></i><span data-i18n="workday">Kerja</span></span>
                <span><i class="legend-half"></i><span data-i18n="half_day">1/2 hari</span></span>
                <span><i class="legend-quarter"></i><span data-i18n="quarter_day">1/4 hari</span></span>
                <span><i class="legend-off"></i><span data-i18n="holiday">Libur</span></span>
            </div>
            <div class="calendar-help" data-i18n="calendar_help">Klik tanggal untuk mengganti status. Minggu bisa dijadikan kerja bila diperlukan.</div>
            <div class="modal-actions">
                <span class="workday-message" id="workdayMessage"></span>
                <button type="button" id="workdayCancel" data-i18n="cancel">Batal</button>
                <button type="button" class="primary" id="workdaySave" data-i18n="save">Simpan</button>
            </div>
        </div>
    </section>
</div>

<div class="modal-backdrop" id="calendarLoginModal" role="dialog" aria-modal="true" aria-labelledby="calendarLoginTitle">
    <section class="detail-modal login-modal">
        <div class="modal-head">
            <h3 id="calendarLoginTitle">Login Production Calender</h3>
            <button type="button" class="modal-close" id="calendarLoginClose" aria-label="Tutup login">&times;</button>
        </div>
        <div class="modal-body">
            <div class="login-fields">
                <label><span data-i18n="username">Username</span><input type="text" id="calendarUsername" autocomplete="username"></label>
                <label><span data-i18n="password">Password</span><input type="password" id="calendarPassword" autocomplete="current-password"></label>
            </div>
            <div class="modal-actions">
                <span class="login-message" id="calendarLoginMessage"></span>
                <button type="button" id="calendarLoginCancel" data-i18n="cancel">Batal</button>
                <button type="button" class="primary" id="calendarLoginSubmit" data-i18n="login">Login</button>
            </div>
        </div>
    </section>
</div>

<div class="modal-backdrop" id="analyticsMenuModal" role="dialog" aria-modal="true" aria-labelledby="analyticsMenuTitle">
    <section class="detail-modal analytics-menu">
        <div class="modal-head">
            <h3 id="analyticsMenuTitle">Analytics Display</h3>
            <button type="button" class="modal-close" id="analyticsMenuClose" aria-label="Tutup analytics display">&times;</button>
        </div>
        <div class="modal-body">
            <div class="analytics-card-options" id="analyticsCardOptions"></div>
            <div class="modal-actions">
                <span class="workday-message" id="analyticsMenuMessage"></span>
                <button type="button" id="analyticsMenuReset" data-i18n="default">Default</button>
                <button type="button" id="analyticsMenuCancel" data-i18n="cancel">Batal</button>
                <button type="button" class="primary" id="analyticsMenuSave" data-i18n="save">Simpan</button>
            </div>
        </div>
    </section>
</div>

<div class="modal-backdrop" id="managementModal" role="dialog" aria-modal="true">
    <section class="detail-modal management-modal">
        <div class="modal-head">
            <div></div>
            <button type="button" class="modal-close" id="managementModalClose" aria-label="Tutup management">&times;</button>
        </div>
        <div class="modal-body management-body">
            <p class="management-note" data-i18n="management_note">Satu login untuk delivery, production calendar, riwayat QTY, dan analytics display.</p>
            <div class="management-grid">
                <section class="management-card">
                    <div class="management-card-head">
                        <b data-i18n="delivery_selection">Delivery Selection</b>
                        <span>2 / 4</span>
                    </div>
                    <div class="management-delivery-toggle" id="managementDeliveryToggle" aria-label="Jumlah delivery">
                        <button type="button" data-delivery-count="2">2 Delivery</button>
                        <button type="button" class="active" data-delivery-count="4">4 Delivery</button>
                    </div>
                    <p data-i18n="delivery_note">Pilih horizon delivery yang sedang aktif.</p>
                </section>
                <section class="management-card">
                    <div class="management-card-head">
                        <b data-i18n="production_calendar">Production Calender</b>
                        <span data-i18n="calendar_access">Calendar</span>
                    </div>
                    <p data-i18n="calendar_note">Atur hari kerja, libur, dan penyesuaian kalender produksi.</p>
                    <button type="button" class="primary" id="managementCalendarOpen" data-i18n="open">Buka</button>
                </section>
                <section class="management-card">
                    <div class="management-card-head">
                        <b data-i18n="qty_history">Riwayat QTY</b>
                        <span data-i18n="history_access">History</span>
                    </div>
                    <p data-i18n="qty_history_note">Lihat histori harian QTY PDK, output, dan balance.</p>
                    <button type="button" class="primary" id="managementQtyOpen" data-i18n="open">Buka</button>
                </section>
                <section class="management-card">
                    <div class="management-card-head">
                        <b data-i18n="analyticsDisplay">Analytics Display</b>
                        <span data-i18n="display_access">Display</span>
                    </div>
                    <p data-i18n="analytics_note">Atur kartu analytics yang ingin ditampilkan di dashboard.</p>
                    <button type="button" class="primary" id="managementAnalyticsOpen" data-i18n="open">Buka</button>
                </section>
            </div>
        </div>
    </section>
</div>

<div class="modal-backdrop" id="qtyHistoryModal" role="dialog" aria-modal="true" aria-labelledby="qtyHistoryModalTitle">
    <section class="detail-modal" style="width:min(1080px,94vw)">
        <div class="modal-head">
            <h3 id="qtyHistoryModalTitle" data-i18n="qty_history_title">Riwayat QTY Harian</h3>
            <button type="button" class="modal-close" id="qtyHistoryModalClose" aria-label="Tutup riwayat">&times;</button>
        </div>
        <div class="modal-body">
            <div class="calendar-tools">
                <button type="button" id="qtyHistoryRange" style="background:var(--brand);color:#fff;border-color:var(--brand);font-weight:850;cursor:default;padding:0 16px;">7 Hari Terakhir</button>
                <button type="button" class="primary" id="qtyHistoryRefresh" style="background:var(--brand);color:#fff;">Refresh</button>
            </div>
            <div class="summary-grid" id="qtyHistorySummary" style="margin-bottom:10px;display:grid;grid-template-columns:repeat(4,1fr);gap:8px;"></div>
            <div style="border:1px solid var(--grid);border-radius:8px;overflow:auto;max-height:calc(100vh - 320px);">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr>
                            <th style="padding:8px 10px;text-align:center;position:sticky;top:0;background:var(--surface-soft);z-index:2;">Tanggal</th>
                            <th style="padding:8px 10px;text-align:center;position:sticky;top:0;background:var(--surface-soft);z-index:2;">QTY PDK</th>
                            <th style="padding:8px 10px;text-align:center;position:sticky;top:0;background:var(--surface-soft);z-index:2;">QTY Output</th>
                            <th style="padding:8px 10px;text-align:center;position:sticky;top:0;background:var(--surface-soft);z-index:2;">Balance QTY</th>
                            <th style="padding:8px 10px;text-align:center;position:sticky;top:0;background:var(--surface-soft);z-index:2;">Catatan</th>
                        </tr>
                    </thead>
                    <tbody id="qtyHistoryRows"></tbody>
                </table>
            </div>
            <div id="qtyHistoryEmpty" class="detail-empty" style="display:none;">Data riwayat belum tersedia.</div>
        </div>
    </section>
</div>

<script>
const urls = {
    status: <?= json_encode($status_url) ?>,
    saveWorkdays: <?= json_encode($save_workdays_url) ?>,
    calendarLogin: <?= json_encode($calendar_login_url) ?>,
    calendarLogout: <?= json_encode($calendar_logout_url) ?>,
    run: <?= json_encode($run_url) ?>,
    download: <?= json_encode($download_url) ?>,
    qtyHistory: <?= json_encode($qty_history_url) ?>
};
const featureVisibility = {
    criticalOrders: false,
    internalAnalytics: false
};
const analyticsCardStorageKey = 'heatAnalyticsVisibleCards';
const analyticsLangStorageKey = 'heatAnalyticsLanguage';
const defaultAnalyticsCards = [
    'production_status',
    'output_achievement',
    'data_accuracy'
];
const analyticsText = {
    en: {
        app_title: 'DASHBOARD HEAT TRANSFER',
        dashboard: 'Dashboard',
        analytic: 'Analytic',
        production_calendar: 'Production Calendar',
        last_data: 'Latest Data',
        last_update: 'Last Update',
        total_output_label: 'Total Output :',
        balance_qty_label: 'Balance Qty :',
        qty_short_label: 'Short Qty :',
        qty_pdk_output_chart: 'QTY PDK vs QTY OUTPUT',
        ready_to_load_chart: 'READY TO LOAD PRODUCTION',
        capacity_output_input_chart: 'CAPACITY vs OUTPUT vs INPUT',
        qty_pdk: 'QTY PDK',
        qty_out: 'QTY OUT',
        input: 'Input',
        top_priority_title: 'Top 10 Priority Orders Ready For Production :',
        delivery_date: 'Delivery Date',
        qty_ready: 'Qty Ready',
        main_condition: 'Main Area Condition :',
        shortage: 'Shortage',
        overall_level: 'Overall Level',
        management_summary: 'Management Summary :',
        data_accuracy_issues: 'Data Accuracy Issues',
        prevention_handling: 'Prevention & Handling :',
        previous: 'Previous',
        next: 'Next',
        sunday_off: 'Sunday/off',
        workday: 'Workday',
        half_day: '1/2 day',
        quarter_day: '1/4 day',
        holiday: 'Holiday',
        calendar_help: 'Click a date to change status. Sunday can be set as workday when needed.',
        cancel: 'Cancel',
        save: 'Save',
        default: 'Default',
        username: 'Username',
        password: 'Password',
        login_calendar: 'Login Production Calendar',
        login: 'Login',
        close_detail: 'Close detail',
        close_calendar: 'Close calendar',
        close_login: 'Close login',
        close_analytics_display: 'Close analytics display',
        detail: 'Detail',
        detail_empty: 'Detail data is not available.',
        no_capacity_data: 'KAPASITAS vs OUTPUT chart data is not available.',
        no_short_qty: 'No short qty.',
        dashboard_unavailable: 'Dashboard data is not available.',
        logging_in: 'Logging in...',
        login_failed: 'Login failed.',
        saving: 'Saving...',
        save_failed: 'Failed to save workdays.',
        saved: 'Saved.',
        choose_one_card: 'Choose at least 1 card.',
        valid_sequence: 'Data sequence valid',
        valid_sequence_text: 'No period sequence issue detected.',
        capacity: 'Capacity',
        input_32a: 'Input 32a',
        output: 'Output',
        gap: 'Gap',
        surplus: 'Surplus',
        ready_periods: 'Ready periods',
        required_daily_output_label: 'Required daily output',
        production_status_label: 'Production Status:',
        problem: 'Problem',
        cause: 'Cause',
        prevention: 'Prevention',
        handling: 'Handling',
        result: 'Result',
        analyticsTitle: 'Data-Driven & Management Analytics :',
        insightTitle: 'Management Insight :',
        analyticsDisplay: 'Analytics Display',
        management_panel: 'Management Access',
        open_management: 'Management',
        management_note: 'One login for delivery, production calendar, qty history, and analytics display.',
        delivery_selection: 'Delivery Selection',
        delivery_note: 'Choose the active delivery horizon.',
        calendar_access: 'Calendar',
        calendar_note: 'Adjust working days, holidays, and production calendar settings.',
        history_access: 'History',
        qty_history_note: 'Review daily QTY PDK, output, and balance history.',
        display_access: 'Display',
        analytics_note: 'Choose which analytics cards are shown on the dashboard.',
        login_management: 'Login Management',
        open: 'Open',
        production_status: 'Production Status',
        output_achievement: 'Output Achievement',
        data_accuracy: 'Data Accuracy',
        plan_completion: 'Plan Completion',
        monitoring_coverage: 'Monitoring Coverage',
        source_sync: 'Source Sync',
        data_update: 'Data Update',
        trend: 'Trend',
        production_flow: 'Production Flow',
        data_reliability: 'Data Reliability',
        ready_coverage: 'Ready Coverage',
        req_daily_output: 'Req. Daily Output',
        total_ready_load: 'Total Ready Load',
        avg_daily_output: 'Avg Daily Output',
        avg_daily_capacity: 'Avg Daily Capacity',
        capacity_gap: 'Capacity Gap',
        capacity_surplus: 'Capacity Surplus',
        sequence_issues: 'Sequence Issues',
        critical_orders: 'Critical Orders',
        cap_data_accuracy: 'CAP Data Accuracy',
        cap_output_achievement: 'CAP Output Achievement',
        cap_coverage_ready: 'CAP Coverage Ready Load',
        cap_daily_output: 'CAP Daily Output Requirement',
        cap_critical_order: 'CAP Critical Delivery Order',
        cap_controlled: 'CAP Controlled Condition',
        note_public_status: 'General status without operational detail.',
        note_percent_output: 'Production performance percentage.',
        note_percent_accuracy: 'Data accuracy percentage.',
        note_plan_completion: 'Completion percentage from output data.',
        note_monitoring: 'Coverage of dashboard modules being monitored.',
        note_source_sync: 'Number of synced data sources.',
        note_update: 'Latest update time from each data source.',
        note_trend: 'General trend status.',
        note_flow: 'General production flow status.',
        note_reliability: 'Reliability based on data accuracy.',
        note_internal_ready: 'Ready-load coverage data.',
        note_internal_daily: 'Daily output requirement data.',
        note_internal_total_ready: 'Total ready-load data.',
        note_internal_avg_output: 'Average daily output data.',
        note_internal_capacity: 'Average daily capacity data.',
        note_internal_gap: 'Capacity gap/surplus data.',
        note_internal_sequence: 'Data sequence issue count.',
        note_internal_critical: 'Critical order count.',
        note_internal_cap: 'Issue, cause, prevention, handling.',
        note_internal_cap_simple: 'Prevention and handling.',
        item: 'Item',
        description: 'Description',
        card_value: 'Card Value',
        display_mode: 'Display Mode',
        summary_view: 'Summary view',
        display_note: 'Shows the dashboard source data used by this card.',
        formula: 'Formula',
        module: 'Module',
        source: 'Source',
        status: 'Status',
        validation: 'Validation',
        detail_qty_hidden: 'The percentage shows completion against plan. The table below shows the dashboard rows used by this card.',
        production_status_formula: 'Production Status is summarized from active delivery condition, output achievement, and data validation.',
        output_formula: 'Achievement = completed output / PDK plan x 100%.',
        output_source: 'Plan and output data from synced RPA files.',
        accuracy_formula: 'Score = 100% minus data-sequence issue penalties.',
        monitoring_note: 'Monitoring Coverage shows dashboard modules included in the monitoring scope.',
        source_sync_formula: 'Source Sync compares available data sources against total monitored sources.',
        data_update_formula: 'Data Update shows the latest timestamp recorded from each dashboard source.',
        general_indicator_note: 'This card is used as a general review indicator.',
        detail_unavailable: 'Card detail is not available yet.',
        monitored: 'Monitored',
        synced: 'Synced',
        missing: 'Missing',
        today: 'Today',
        stable: 'Stable',
        on_track: 'On Track',
        pcs: 'Pcs',
        days: 'Days',
        issue: 'Issue',
        order: 'Order',
        output_insight: 'Output Achievement Insight',
        output_insight_text: 'Output performance on track at {value}%.',
        reliability: 'Data Reliability',
        reliability_text: 'Dashboard data validation is stable at {value}%.',
        flow_text: 'Production progress is aligned with the active delivery plan.',
        monitoring_status: 'Monitoring Status',
        monitoring_text: 'Dashboard monitoring is active and ready for review.',
        analytics_ready: 'Analytics display is ready.',
        production_status_insight: 'Production is {status}. Dashboard total output is {output} pcs with balance {balance} pcs.',
        output_achievement_insight: 'Output achievement is {value} based on dashboard output {output} pcs.',
        data_accuracy_insight: 'Data accuracy is {value}; validation follows the period rows shown in dashboard detail.',
        monitoring_coverage_insight: 'Monitoring covers {value} of active dashboard modules.',
        source_sync_insight: 'Source sync is {value}; available source data is ready for dashboard calculation.',
        data_update_insight: 'Latest dashboard data update is {value}.',
        ready_coverage_insight: 'Ready coverage is {value}; total ready load is {ready} pcs.',
        req_daily_output_insight: 'Required daily output is {value}; dashboard balance is {balance} pcs.',
        total_ready_load_insight: 'Total ready load is {value}, taken from dashboard ready-to-load data.',
        avg_daily_output_insight: 'Average daily output is {value}, compared with current dashboard capacity.',
        avg_daily_capacity_insight: 'Average daily capacity is {value}, based on active delivery capacity calculation.',
        capacity_gap_insight: '{label} is {value}; this compares daily output with available capacity.',
        sequence_issues_insight: 'Sequence issues detected: {value}; this affects data validation status.',
        critical_orders_insight: 'Critical orders detected: {value}; detail follows the priority order list.',
        cap_insight: '{label} is displayed as a follow-up item for the selected analytics card.'
    },
    id: {
        app_title: 'DASHBOARD HEAT TRANSFER',
        dashboard: 'Dashboard',
        analytic: 'Analytic',
        production_calendar: 'Production Calender',
        last_data: 'Data Terakhir',
        last_update: 'Last Update',
        total_output_label: 'Total Output :',
        balance_qty_label: 'Balance Qty :',
        qty_short_label: 'Qty Yang Kurang :',
        qty_pdk_output_chart: 'QTY PDK vs QTY OUTPUT',
        ready_to_load_chart: 'READY TO LOAD PRODUCTION',
        capacity_output_input_chart: 'KAPASITAS vs OUTPUT vs INPUT',
        qty_pdk: 'QTY PDK',
        qty_out: 'QTY OUT',
        input: 'Input',
        top_priority_title: 'Top 10 Priority Orders Ready For Production :',
        delivery_date: 'Tgl. Delivery',
        qty_ready: 'Qty Ready',
        main_condition: 'Kondisi Utama Area :',
        shortage: 'Kurang',
        overall_level: 'Overall Level',
        management_summary: 'Management Summary :',
        data_accuracy_issues: 'Data Accuracy Issues',
        prevention_handling: 'Prevention & Handling :',
        previous: 'Sebelumnya',
        next: 'Berikutnya',
        sunday_off: 'Minggu/off',
        workday: 'Kerja',
        half_day: '1/2 hari',
        quarter_day: '1/4 hari',
        holiday: 'Libur',
        calendar_help: 'Klik tanggal untuk mengganti status. Minggu bisa dijadikan kerja bila diperlukan.',
        cancel: 'Batal',
        save: 'Simpan',
        default: 'Default',
        username: 'Username',
        password: 'Password',
        login_calendar: 'Login Production Calender',
        login: 'Login',
        close_detail: 'Tutup detail',
        close_calendar: 'Tutup kalender',
        close_login: 'Tutup login',
        close_analytics_display: 'Tutup analytics display',
        detail: 'Detail',
        detail_empty: 'Data detail belum tersedia.',
        no_capacity_data: 'Data grafik KAPASITAS vs OUTPUT belum tersedia.',
        no_short_qty: 'Tidak ada qty kurang.',
        dashboard_unavailable: 'Data dashboard belum tersedia.',
        logging_in: 'Login...',
        login_failed: 'Login gagal.',
        saving: 'Menyimpan...',
        save_failed: 'Gagal menyimpan hari kerja.',
        saved: 'Tersimpan.',
        choose_one_card: 'Pilih minimal 1 card.',
        valid_sequence: 'Urutan data valid',
        valid_sequence_text: 'Tidak ada masalah urutan periode yang terdeteksi.',
        capacity: 'Kapasitas',
        input_32a: 'Input 32a',
        output: 'Output',
        gap: 'Gap',
        surplus: 'Surplus',
        ready_periods: 'Ready periods',
        required_daily_output_label: 'Required daily output',
        production_status_label: 'Production Status:',
        problem: 'Masalah',
        cause: 'Penyebab',
        prevention: 'Pencegahan',
        handling: 'Penanganan',
        result: 'Hasil',
        analyticsTitle: 'Analitik Berbasis Data :',
        insightTitle: 'Insight Manajemen :',
        analyticsDisplay: 'Tampilan Analytics',
        management_panel: 'Akses Manajemen',
        open_management: 'Manajemen',
        management_note: 'Satu login untuk delivery, production calendar, riwayat QTY, dan analytics display.',
        delivery_selection: 'Pemilihan Delivery',
        delivery_note: 'Pilih horizon delivery yang sedang aktif.',
        calendar_access: 'Kalender',
        calendar_note: 'Atur hari kerja, libur, dan pengaturan production calendar.',
        history_access: 'Riwayat',
        qty_history_note: 'Lihat histori harian QTY PDK, output, dan balance.',
        display_access: 'Tampilan',
        analytics_note: 'Pilih kartu analytics yang ingin ditampilkan di dashboard.',
        login_management: 'Login Manajemen',
        open: 'Buka',
        production_status: 'Status Produksi',
        output_achievement: 'Pencapaian Output',
        data_accuracy: 'Akurasi Data',
        plan_completion: 'Penyelesaian Plan',
        monitoring_coverage: 'Cakupan Monitoring',
        source_sync: 'Sinkronisasi Sumber',
        data_update: 'Update Data',
        trend: 'Tren',
        production_flow: 'Alur Produksi',
        data_reliability: 'Reliabilitas Data',
        ready_coverage: 'Coverage Ready',
        req_daily_output: 'Kebutuhan Output Harian',
        total_ready_load: 'Total Ready Load',
        avg_daily_output: 'Rata-rata Output Harian',
        avg_daily_capacity: 'Rata-rata Kapasitas Harian',
        capacity_gap: 'Gap Kapasitas',
        capacity_surplus: 'Surplus Kapasitas',
        sequence_issues: 'Sequence Issues',
        critical_orders: 'Order Kritis',
        cap_data_accuracy: 'CAP Akurasi Data',
        cap_output_achievement: 'CAP Pencapaian Output',
        cap_coverage_ready: 'CAP Coverage Ready Load',
        cap_daily_output: 'CAP Kebutuhan Output Harian',
        cap_critical_order: 'CAP Order Delivery Kritis',
        cap_controlled: 'CAP Kondisi Terkendali',
        note_public_status: 'Status umum tanpa detail operasional.',
        note_percent_output: 'Persentase performa produksi.',
        note_percent_accuracy: 'Persentase akurasi data.',
        note_plan_completion: 'Persentase completion dari data output.',
        note_monitoring: 'Cakupan modul dashboard yang sedang dipantau.',
        note_source_sync: 'Jumlah sumber data yang tersinkron.',
        note_update: 'Waktu update terakhir dari setiap sumber data.',
        note_trend: 'Status tren umum.',
        note_flow: 'Status alur produksi umum.',
        note_reliability: 'Reliabilitas berdasarkan akurasi data.',
        note_internal_ready: 'Data coverage ready load.',
        note_internal_daily: 'Data kebutuhan output harian.',
        note_internal_total_ready: 'Data total ready load.',
        note_internal_avg_output: 'Data rata-rata output harian.',
        note_internal_capacity: 'Data rata-rata kapasitas harian.',
        note_internal_gap: 'Data gap/surplus kapasitas.',
        note_internal_sequence: 'Jumlah issue urutan data.',
        note_internal_critical: 'Jumlah order kritis.',
        note_internal_cap: 'Masalah, penyebab, pencegahan, penanganan.',
        note_internal_cap_simple: 'Pencegahan dan penanganan.',
        item: 'Item',
        description: 'Keterangan',
        card_value: 'Nilai Card',
        display_mode: 'Mode Tampilan',
        summary_view: 'Tampilan ringkas',
        display_note: 'Menampilkan data dashboard yang dipakai untuk membentuk card ini.',
        formula: 'Formula',
        module: 'Modul',
        source: 'Sumber',
        last_update: 'Update Terakhir',
        status: 'Status',
        validation: 'Validasi',
        detail_qty_hidden: 'Persentase menunjukkan tingkat penyelesaian terhadap plan. Tabel di bawah menampilkan baris dashboard yang dipakai oleh card ini.',
        production_status_formula: 'Status Produksi diringkas dari kondisi delivery aktif, pencapaian output, dan validasi data.',
        output_formula: 'Achievement = output selesai / plan PDK x 100%.',
        output_source: 'Data plan dan output dari file RPA yang sudah tersinkron.',
        accuracy_formula: 'Score = 100% dikurangi penalti issue urutan data.',
        monitoring_note: 'Cakupan Monitoring menunjukkan modul dashboard yang masuk area pantauan.',
        source_sync_formula: 'Sinkronisasi Sumber membandingkan sumber data tersedia dengan total sumber yang dipantau.',
        data_update_formula: 'Update Data menunjukkan timestamp terakhir yang tercatat dari setiap sumber dashboard.',
        general_indicator_note: 'Card ini dipakai sebagai indikator ringkas untuk review umum.',
        detail_unavailable: 'Detail card belum tersedia.',
        monitored: 'Dipantau',
        synced: 'Sinkron',
        missing: 'Belum Ada',
        today: 'Hari Ini',
        stable: 'Stabil',
        on_track: 'On Track',
        pcs: 'Pcs',
        days: 'Hari',
        issue: 'Issue',
        order: 'Order',
        output_insight: 'Insight Pencapaian Output',
        output_insight_text: 'Performa output berjalan sesuai target di {value}%.',
        reliability: 'Reliabilitas Data',
        reliability_text: 'Validasi data dashboard stabil di {value}%.',
        flow_text: 'Progress produksi selaras dengan plan delivery aktif.',
        monitoring_status: 'Status Monitoring',
        monitoring_text: 'Monitoring dashboard aktif dan siap direview.',
        analytics_ready: 'Tampilan analytics siap.',
        production_status_insight: 'Produksi {status}. Total output dashboard {output} pcs dengan balance {balance} pcs.',
        output_achievement_insight: 'Pencapaian output {value} berdasarkan output dashboard {output} pcs.',
        data_accuracy_insight: 'Akurasi data {value}; validasi mengikuti baris periode pada detail dashboard.',
        monitoring_coverage_insight: 'Monitoring mencakup {value} modul dashboard aktif.',
        source_sync_insight: 'Sinkronisasi sumber {value}; data source tersedia untuk kalkulasi dashboard.',
        data_update_insight: 'Update data dashboard terakhir: {value}.',
        ready_coverage_insight: 'Coverage ready {value}; total ready load {ready} pcs.',
        req_daily_output_insight: 'Kebutuhan output harian {value}; balance dashboard {balance} pcs.',
        total_ready_load_insight: 'Total ready load {value}, diambil dari data ready-to-load dashboard.',
        avg_daily_output_insight: 'Rata-rata output harian {value}, dibandingkan dengan kapasitas dashboard saat ini.',
        avg_daily_capacity_insight: 'Rata-rata kapasitas harian {value}, berdasarkan kalkulasi kapasitas delivery aktif.',
        capacity_gap_insight: '{label} sebesar {value}; membandingkan output harian dengan kapasitas tersedia.',
        sequence_issues_insight: 'Sequence issues terdeteksi: {value}; ini memengaruhi status validasi data.',
        critical_orders_insight: 'Order kritis terdeteksi: {value}; detail mengikuti daftar order prioritas.',
        cap_insight: '{label} ditampilkan sebagai item tindak lanjut dari card analytics yang dipilih.'
    }
};
const analyticsCardDefinitions = [
    {key:'production_status', note:'note_public_status'},
    {key:'output_achievement', note:'note_percent_output'},
    {key:'data_accuracy', note:'note_percent_accuracy'},
    {key:'monitoring_coverage', note:'note_monitoring'},
    {key:'source_sync', note:'note_source_sync'},
    {key:'data_update', note:'note_update'},
    {key:'ready_coverage', note:'note_internal_ready'},
    {key:'req_daily_output', note:'note_internal_daily'},
    {key:'total_ready_load', note:'note_internal_total_ready'},
    {key:'avg_daily_output', note:'note_internal_avg_output'},
    {key:'avg_daily_capacity', note:'note_internal_capacity'},
    {key:'capacity_gap', note:'note_internal_gap'},
    {key:'sequence_issues', note:'note_internal_sequence'},
    {key:'critical_orders', note:'note_internal_critical'},
    {key:'cap_data_accuracy', note:'note_internal_cap'},
    {key:'cap_output_achievement', note:'note_internal_cap'},
    {key:'cap_coverage_ready', note:'note_internal_cap'},
    {key:'cap_daily_output', note:'note_internal_cap'},
    {key:'cap_critical_order', note:'note_internal_cap'},
    {key:'cap_controlled', note:'note_internal_cap_simple'}
];
const rupiah = new Intl.NumberFormat('id-ID');
const percentNumber = new Intl.NumberFormat('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2});
const fmt = (value) => value === null || value === undefined || isNaN(Number(value)) ? '-' : rupiah.format(Number(value));
const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
const dateOnly = (value) => value ? new Intl.DateTimeFormat('id-ID', {day:'2-digit', month:'long', year:'numeric'}).format(new Date(value)) : '-';
const dateTime = (value) => value ? new Intl.DateTimeFormat('id-ID', {day:'2-digit', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit'}).format(new Date(value)) : '-';
let analyticsLanguage = localStorage.getItem(analyticsLangStorageKey) === 'en' ? 'en' : 'id';
const text = (key) => analyticsText[analyticsLanguage]?.[key] || analyticsText.en[key] || key;
const textTemplate = (key, values = {}) => Object.keys(values).reduce(
    (result, name) => result.replaceAll(`{${name}}`, values[name]),
    text(key)
);

function renderAppLanguage() {
    document.documentElement.lang = analyticsLanguage === 'en' ? 'en' : 'id';
    document.querySelectorAll('[data-i18n]').forEach(element => {
        element.textContent = text(element.dataset.i18n);
    });
    document.querySelectorAll('[data-analytics-lang]').forEach(button => {
        button.classList.toggle('active', button.dataset.analyticsLang === analyticsLanguage);
    });
    document.getElementById('analyticsMenuTitle').textContent = text('analyticsDisplay');
    document.getElementById('detailModalClose').setAttribute('aria-label', text('close_detail'));
    document.getElementById('workdayModalClose').setAttribute('aria-label', text('close_calendar'));
    document.getElementById('calendarLoginClose').setAttribute('aria-label', text('close_login'));
    document.getElementById('analyticsMenuClose').setAttribute('aria-label', text('close_analytics_display'));
}
let currentDashboard = null;
let calendarCursor = new Date();
let selectedHolidays = new Set();
let selectedHalfDays = new Set();
let selectedQuarterDays = new Set();
let selectedWorkDays = new Set();
let calendarAuthenticated = false;
let pendingLoginAction = null;
let selectedDeliveryCount = Number(localStorage.getItem('heatDeliveryCount')) === 4 ? 4 : 2;

function floorDecimal(value, decimals) {
    const factor = Math.pow(10, decimals);
    return Math.floor((Number(value) || 0) * factor) / factor;
}

function numberValue(...values) {
    for (const value of values) {
        const number = Number(value);
        if (value !== null && value !== undefined && value !== '' && !isNaN(number)) {
            return number;
        }
    }

    return 0;
}

function shortDelivery(value) {
    if (!value) return '';
    return String(value).replace(/\s+202\d$/, '').replace(/\s+/g, '-');
}

function renderEmpty(data) {
    renderAppLanguage();
    document.querySelector('.dashboard').innerHTML = `<div class="box" style="grid-column:1/-1;padding:24px;text-align:center">${esc(data?.message || text('dashboard_unavailable'))}</div>`;
}

function compactChartValue(value) {
    const number = Number(value) || 0;
    if (Math.abs(number) >= 1000) {
        const compact = number / 1000;
        const rounded = compact >= 10 ? Math.round(compact) : floorDecimal(compact, 1);
        return `${fmt(rounded)}K`;
    }

    return fmt(Math.round(number));
}

function renderGroupedChart(target, rows, keys, options = {}) {
    const displayRows = options.limit ? rows.slice(-options.limit) : rows;
    const max = Math.max(...displayRows.flatMap(row => keys.map(key => Number(row[key]) || 0)), 1);
    const maxBarHeight = Math.max(72, target.clientHeight - 72);
    target.innerHTML = `
        <div class="chart-area ${options.clean ? 'clean' : ''}">
            ${displayRows.map(row => `
                <div class="group">
                    <div class="bars">
                        ${keys.map((key, index) => {
                            const value = Number(row[key]) || 0;
                            const height = Math.max(2, value / max * maxBarHeight);
                            const rounded = Math.round(value);
                            const className = index === 0 ? '' : (index === 1 ? 'alt' : 'third');
                            const label = options.compactLabels ? compactChartValue(rounded) : fmt(rounded);
                            const title = `${key.toUpperCase()}: ${fmt(rounded)} pcs`;
                            return `<div class="vbar ${className}" style="height:${height}px" title="${esc(title)}"><small class="label-${index}">${label}</small></div>`;
                        }).join('')}
                    </div>
                    <div class="glabel">${esc(row.label)}</div>
                </div>
            `).join('')}
        </div>
    `;
}

function renderCapacityChart(target, rows) {
    const keys = ['capacity', 'output', 'input'];
    const maxVal = Math.max(...rows.flatMap(row => keys.map(key => Number(row[key]) || 0)), 1);

    // Calculate Y-axis step and ticks
    const possibleSteps = [500, 1000, 2000, 2500, 5000, 10000, 20000, 25000, 50000, 100000];
    let step = possibleSteps[possibleSteps.length - 1];
    for (const s of possibleSteps) {
        const ticksCount = Math.ceil(maxVal / s);
        if (ticksCount >= 4 && ticksCount <= 8) {
            step = s;
            break;
        }
    }
    const roundedMax = Math.ceil(maxVal / step) * step;
    const ticks = [];
    for (let val = 0; val <= roundedMax; val += step) {
        ticks.push(val);
    }

    const bottomOffset = 25; // glabel area height at bottom
    const maxBarHeight = Math.max(72, target.clientHeight - 72);

    const barWidth = Math.max(34, Math.min(48, 0.0245 * window.innerWidth));
    const gap = Math.max(4, Math.min(7, 0.0034 * window.innerWidth));
    const pixelOffset = (barWidth + gap) / 2;
    const chartWidth = Math.max(100, target.clientWidth - 64);
    const shiftPercent = (pixelOffset / chartWidth) * 100;

    const inputPoints = rows.map((row, index) => {
        const xCenter = rows.length > 0 ? ((index + 0.5) / rows.length) * 100 : 50;
        const x = xCenter + shiftPercent;
        const rawY = maxBarHeight - ((Number(row.input) || 0) / roundedMax * maxBarHeight);
        const y = Math.max(0, Math.min(maxBarHeight, rawY));
        const labelY = Math.max(0, Math.min(maxBarHeight - 18, y + 10));
        return {x, y, labelY, value: Number(row.input) || 0, label: row.label || '-'};
    });
    const inputPath = inputPoints.map(point => `${point.x.toFixed(2)},${point.y.toFixed(2)}`).join(' ');

    target.innerHTML = `
        <div class="chart-area" style="flex: 1; min-height: 0; padding: 0;">
            <!-- Gridlines -->
            ${ticks.map(tick => {
                const bottomPos = bottomOffset + (tick / roundedMax) * maxBarHeight;
                return `<div class="chart-gridline" style="bottom: ${bottomPos.toFixed(1)}px; left: 52px; right: 12px;"></div>`;
            }).join('')}
            
            <!-- Y-axis labels -->
            ${ticks.map(tick => {
                const bottomPos = bottomOffset + (tick / roundedMax) * maxBarHeight;
                return `<div class="chart-y-label" style="bottom: ${bottomPos.toFixed(1)}px; left: 0; width: 44px; text-align: right; transform: translateY(50%);">${fmt(tick)}</div>`;
            }).join('')}
            
            <!-- Input Line Chart SVG with circles -->
            <svg class="input-line-chart" viewBox="0 0 100 ${maxBarHeight}" preserveAspectRatio="none" style="height:${maxBarHeight}px; left: 52px; right: 12px; width: calc(100% - 64px); bottom: ${bottomOffset}px;" aria-hidden="true">
                <polyline points="${esc(inputPath)}"></polyline>
                ${inputPoints.map(point => `
                    <circle cx="${point.x.toFixed(2)}" cy="${point.y.toFixed(2)}" r="0.8" fill="#f59e0b"></circle>
                `).join('')}
            </svg>
            
            <!-- Input labels -->
            <div class="input-line-labels" style="height:${maxBarHeight}px; left: 52px; right: 12px; width: calc(100% - 64px); bottom: ${bottomOffset}px;">
                ${inputPoints.map(point => `
                    <span class="input-value" style="left:${point.x.toFixed(2)}%;top:${point.labelY.toFixed(0)}px" title="INPUT ${esc(point.label)}: ${esc(fmt(Math.round(point.value)))} pcs">${esc(fmt(Math.round(point.value)))}</span>
                `).join('')}
            </div>
            
            <!-- Columns/Bars -->
            <div class="chart-columns" style="margin-left: 52px; margin-right: 12px; height: 100%; display: flex; justify-content: space-around; position: relative; z-index: 2; width: calc(100% - 64px);">
                ${rows.map(row => {
                    return `
                    <div class="group">
                        <div class="bars">
                            ${['capacity', 'output'].map((key, index) => {
                                const value = Number(row[key]) || 0;
                                const height = Math.max(2, value / roundedMax * maxBarHeight);
                                const rounded = Math.round(value);
                                const className = index ? 'alt' : '';
                                const title = `${key.toUpperCase()}: ${fmt(rounded)} pcs`;
                                return `<div class="vbar ${className}" style="height:${height}px" title="${esc(title)}"><small class="label-${index}">${fmt(rounded)}</small></div>`;
                            }).join('')}
                        </div>
                        <div class="glabel">${esc(row.label)}</div>
                    </div>
                `}).join('')}
            </div>
        </div>
    `;
}

function tableCell(value, numeric = false) {
    if (numeric) {
        return `<td class="num">${fmt(Math.round(Number(value) || 0))}</td>`;
    }

    return `<td>${esc(value || '-')}</td>`;
}

function metricValue(item) {
    const value = item?.value;
    const numeric = value !== null && value !== undefined && value !== '' && !isNaN(Number(value));
    if (numeric && item?.suffix === '%') {
        return percentNumber.format(Number(value));
    }
    return numeric ? fmt(value) : esc(value || '-');
}

function renderCalcNote(items, formula) {
    const cards = items.map(item => `
        <div class="calc-item">
            <span>${esc(item[0])}</span>
            <strong>${esc(item[1])}</strong>
        </div>
    `).join('');

    return `
        <div class="calc-note">
            ${cards}
            <div class="calc-formula">${esc(formula)}</div>
        </div>
    `;
}

function renderDetailTable(title, headers, rows, calcItems = [], formula = '') {
    document.getElementById('detailModalTitle').textContent = title;
    const calcNote = calcItems.length || formula ? renderCalcNote(calcItems, formula) : '';
    document.getElementById('detailModalBody').innerHTML = calcNote + (rows.length ? `
        <div class="detail-table">
            <table>
                <thead><tr>${headers.map(header => `<th>${esc(header)}</th>`).join('')}</tr></thead>
                <tbody>${rows.join('')}</tbody>
            </table>
        </div>
    ` : `<div class="detail-empty">${esc(text('detail_empty'))}</div>`);
    showDetailModal();
}

function showDetailModal() {
    const modal = document.getElementById('detailModal');
    modal.classList.add('open');
    modal.style.display = 'flex';
}

function closeDetailModal() {
    const modal = document.getElementById('detailModal');
    modal.classList.remove('open');
    modal.style.display = 'none';
}

function openAnalyticsDetail(key) {
    const dashboard = currentDashboard || {};
    const analytics = dashboard.management_analytics || {};
    const details = analytics.details || {};
    const dataAccuracy = analytics.data_accuracy || {};

    if (key === 'output') {
        const output = details.output || {};
        const current = dataAccuracy.current_period || {};
        const totalPdk = Number(output.total_pdk) || 0;
        const totalOutput = Number(output.total_output) || 0;
        const balance = Number(output.balance_qty) || 0;
        const achievement = totalPdk > 0 ? (totalOutput / totalPdk) * 100 : 0;
        const periodRows = current.label
            ? (dashboard.qty_pdk_vs_output || []).filter(row => row.label === current.label)
            : (dashboard.qty_pdk_vs_output || []);
        const rows = periodRows.map(row => `
            <tr>
                ${tableCell(row.label)}
                ${tableCell(row.pdk, true)}
                ${tableCell(row.output, true)}
                ${tableCell((Number(row.pdk) || 0) - (Number(row.output) || 0), true)}
            </tr>
        `);
        renderDetailTable('Detail Output Achievement', ['Period', 'QTY PDK', 'QTY Output', 'Balance'], rows, [
            ['Delivery Berjalan', current.label || '-'],
            ['Total PDK', `${fmt(Math.round(totalPdk))} Pcs`],
            ['Achievement', `${percentNumber.format(floorDecimal(achievement, 2))}%`]
        ], `Achievement = Total Output / Total PDK x 100. Tabel menampilkan delivery/periode yang sedang berjalan. Total output: ${fmt(Math.round(totalOutput))} pcs. Total balance dashboard: ${fmt(Math.round(balance))} pcs.`);
        return;
    }

    if (key === 'ready') {
        const totalReady = Number(details.ready?.total_ready) || 0;
        const avgCapacity = Number(details.ready?.avg_daily_capacity) || 0;
        const coverage = avgCapacity > 0 ? totalReady / avgCapacity : 0;
        const rows = (details.ready?.periods || dashboard.ready_to_load || []).map(row => `
            <tr>
                ${tableCell(row.label)}
                ${tableCell(row.ready, true)}
            </tr>
        `);
        renderDetailTable('Detail Ready Coverage', ['Period', 'Ready Load'], rows, [
            ['Total Ready', `${fmt(Math.round(totalReady))} Pcs`],
            ['Avg Capacity', `${fmt(Math.round(avgCapacity))} Pcs/Day`],
            ['Coverage', `${fmt(coverage.toFixed(1))} Days`]
        ], 'Ready Coverage = Total Ready Load / Avg Daily Capacity. Target aman minimal 10 hari coverage.');
        return;
    }

    if (key === 'delivery') {
        const current = dataAccuracy.current_period || {};
        const checked = dataAccuracy.checked_periods || [];
        const rows = (dataAccuracy.periods || []).map(row => `
            <tr>
                ${tableCell(row.label)}
                ${tableCell(row.pdk, true)}
                ${tableCell(row.output, true)}
                ${tableCell(row.balance, true)}
                ${tableCell(row.ready, true)}
                ${tableCell(row.label === current.label ? 'Berjalan' : (checked.includes(row.label) ? 'Dicek' : '-'))}
            </tr>
        `);
        renderDetailTable('Detail Delivery Berjalan', ['Period', 'PDK', 'Output', 'Balance', 'Ready', 'Status'], rows, [
            ['Delivery Berjalan', current.label || '-'],
            ['Periode Dicek', checked.join(', ') || '-'],
            ['Accuracy', `${percentNumber.format(Number(dataAccuracy.score || 0))}%`]
        ], 'Delivery berjalan ditentukan dari periode pertama yang masih punya balance atau ready. Data Accuracy membandingkan periode berjalan dengan dua periode sebelumnya.');
        return;
    }

    if (key === 'daily') {
        const daily = details.daily_requirement || {};
        const calendar = daily.period_calendar || {};
        const balanceQty = Number(daily.period_balance_qty ?? daily.balance_qty) || 0;
        const daysLeft = Number(daily.period_days_left ?? daily.prod_days_left) || 0;
        const requiredDaily = Number(daily.period_required_daily_output) || (daysLeft > 0 ? balanceQty / daysLeft : 0);
        const avgOutput = Number(daily.avg_daily_output) || 0;
        const latestDaily = (dashboard.output_vs_capacity || []).slice(-1)[0] || {};
        const latestBalanceQty = Number(latestDaily.balance_qty ?? latestDaily.total_demand) || balanceQty;
        const latestSisaHariKerja = Number(latestDaily.sisa_hari_kerja ?? latestDaily.hari_kerja) || daysLeft;
        const latestCapacity = Number(latestDaily.capacity) || (latestSisaHariKerja > 0 ? Math.round(latestBalanceQty / latestSisaHariKerja) : 0);
        const rows = (dashboard.output_vs_capacity || []).map(row => `
            <tr>
                ${tableCell(row.label)}
                ${tableCell(row.output, true)}
                ${tableCell(row.input, true)}
                ${tableCell(row.capacity, true)}
                ${tableCell((Number(row.capacity) || 0) - (Number(row.output) || 0), true)}
                ${tableCell(row.capacity_captured_at ? dateTime(row.capacity_captured_at) : '-')}
                ${tableCell((row.capacity_breakdown || []).map(item => `${item.label}: ${fmt(Math.round(item.balance_qty || item.total_demand || item.total_balance || item.balance || 0))} / ${fmt(Number(item.sisa_hari_kerja || item.hari_kerja || item.total_days_left || item.days_left || 0).toFixed(1))} = ${fmt(Math.round(item.daily_capacity || row.capacity || 0))}`).join(', ') || '-')}
            </tr>
        `);
        renderDetailTable('Detail Daily Output', ['Hari', 'Output', 'Input 32a', 'Capacity', 'Gap', 'Snapshot', 'History Kapasitas'], rows, [
            ['Balance Qty', `${fmt(Math.round(latestBalanceQty))} Pcs`],
            ['Sisa Hari Kerja', `${fmt(latestSisaHariKerja.toFixed(1))} Days`],
            ['Kapasitas/Day', `${fmt(Math.round(latestCapacity))} Pcs/Day`],
            ['Req. Daily', `${fmt(Math.round(requiredDaily))} Pcs/Day`]
        ], `Kapasitas/Day = Balance Qty / Sisa Hari Kerja (balance dari APS). Bar Output dan Input tetap dari Engage 32/32a. Sisa Hari Kerja = sisa hari kerja ke akhir delivery terakhir - buffer export (4 hari x jumlah delivery aktif). Avg Daily Output saat ini: ${fmt(Math.round(avgOutput))} pcs/day. Gap per hari = Capacity - Output.`);
        return;
    }

    if (key === 'accuracy') {
        const issues = dataAccuracy.issues || [];
        const score = Number(dataAccuracy.score) || 0;
        const rows = (dataAccuracy.periods || []).map(row => `
            <tr>
                ${tableCell(row.label)}
                ${tableCell(row.pdk, true)}
                ${tableCell(row.output, true)}
                ${tableCell(row.balance, true)}
                ${tableCell(row.ready, true)}
            </tr>
        `);
        renderDetailTable('Detail Data Accuracy', ['Period', 'PDK', 'Output', 'Balance', 'Ready'], rows, [
            ['Score', `${percentNumber.format(score)}%`],
            ['Sequence Issue', `${fmt(issues.length)} Issue`],
            ['Status', dataAccuracy.status || '-']
        ], 'Score = 100 - (jumlah sequence issue x 20), minimum 0. Issue muncul kalau periode sebelumnya masih punya balance/ready tetapi periode berikutnya sudah output.');
        return;
    }

    if (key === 'critical') {
        const criticalOrders = Number(details.priority?.critical_orders) || 0;
        const rows = (details.priority?.orders || dashboard.top_priority_orders || []).map(row => `
            <tr>
                ${tableCell(row.order)}
                ${tableCell(row.style)}
                ${tableCell(row.delivery)}
                ${tableCell(row.qty_pdk, true)}
                ${tableCell(row.qty_ready, true)}
            </tr>
        `);
        renderDetailTable('Detail Critical Orders', ['Order', 'Style', 'Delivery', 'QTY PDK', 'QTY Ready'], rows, [
            ['Critical Orders', `${fmt(criticalOrders)} Order`],
            ['Horizon', '5 Days'],
            ['Data Ditampilkan', `${fmt(rows.length)} Order`]
        ], 'Critical Orders dihitung dari order prioritas yang tanggal delivery-nya masuk horizon 5 hari kerja kalender.');
    }
}

function detailKeyForMetric(label) {
    return {
        'Output Achievement': 'output',
        'Ready Coverage': 'ready',
        'Req. Daily Output': 'daily',
        'Data Accuracy': 'accuracy'
    }[label] || '';
}

function getVisibleAnalyticsCards() {
    try {
        const saved = JSON.parse(localStorage.getItem(analyticsCardStorageKey) || 'null');
        if (Array.isArray(saved) && saved.length) {
            const validKeys = new Set(analyticsCardDefinitions.map(item => item.key));
            const filtered = saved.filter(key => validKeys.has(key));
            return filtered.length ? filtered : [...defaultAnalyticsCards];
        }
    } catch (error) {
        // Abaikan setting lama yang tidak valid.
    }
    return [...defaultAnalyticsCards];
}

function setVisibleAnalyticsCards(keys) {
    localStorage.setItem(analyticsCardStorageKey, JSON.stringify(keys));
}

function metricByLabel(metrics, label) {
    return metrics.find(item => item.label === label) || {};
}

function analyticsCards(analytics) {
    const metrics = analytics?.metrics || [];
    const summary = analytics?.summary || {};
    const outputMetric = metricByLabel(metrics, 'Output Achievement');
    const accuracyMetric = metricByLabel(metrics, 'Data Accuracy');
    const readyMetric = metricByLabel(metrics, 'Ready Coverage');
    const dailyMetric = metricByLabel(metrics, 'Req. Daily Output');
    const sources = currentDashboard?.sources || [];
    const syncedSources = sources.filter(item => item.exists).length;
    const totalSources = sources.length || 3;
    const capacityGap = Number(summary.capacity_gap || 0);
    const capacityGapLabel = capacityGap < 0 ? 'Capacity Surplus' : 'Capacity Gap';

    const cards = {
        production_status: {label:text('production_status'), value:text('on_track'), suffix:'', status:'good'},
        output_achievement: {...outputMetric, label:text('output_achievement')},
        data_accuracy: {...accuracyMetric, label:text('data_accuracy')},
        plan_completion: {label:text('plan_completion'), value: outputMetric.value ?? 0, suffix:'%', status: outputMetric.status || 'good'},
        monitoring_coverage: {label:text('monitoring_coverage'), value: 100, suffix:'%', status:'good'},
        source_sync: {label:text('source_sync'), value: `${syncedSources}/${totalSources}`, suffix:'', status: syncedSources >= totalSources ? 'good' : 'watch'},
        data_update: {label:text('data_update'), value:text('today'), suffix:'', status:'good'},
        trend: {label:text('trend'), value:text('stable'), suffix:'', status:'good'},
        production_flow: {label:text('production_flow'), value:text('on_track'), suffix:'', status:'good'},
        data_reliability: {label:text('data_reliability'), value: accuracyMetric.value ?? 0, suffix:'%', status: accuracyMetric.status || 'good'},
        ready_coverage: {...readyMetric, label:text('ready_coverage')},
        req_daily_output: {...dailyMetric, label:text('req_daily_output')},
        total_ready_load: {label:text('total_ready_load'), value: Math.round(summary.total_ready || 0), suffix:text('pcs'), status:'good'},
        avg_daily_output: {label:text('avg_daily_output'), value: Math.round(summary.avg_daily_output || 0), suffix:text('pcs'), status:'good'},
        avg_daily_capacity: {label:text('avg_daily_capacity'), value: Math.round(summary.avg_daily_capacity || 0), suffix:text('pcs'), status:'good'},
        capacity_gap: {label:capacityGap < 0 ? text('capacity_surplus') : text('capacity_gap'), value: Math.round(Math.abs(capacityGap)), suffix:text('pcs'), status: capacityGap < 0 ? 'good' : 'watch'},
        sequence_issues: {label:text('sequence_issues'), value: Math.round(summary.sequence_issues || 0), suffix:text('issue'), status: Number(summary.sequence_issues || 0) > 0 ? 'watch' : 'good'},
        critical_orders: {label:text('critical_orders'), value: Math.round(summary.critical_orders || 0), suffix:text('order'), status: Number(summary.critical_orders || 0) > 0 ? 'risk' : 'good'}
    };

    Object.keys(cards).forEach(key => {
        cards[key].key = key;
    });

    return cards;
}

function latestSourceUpdateLabel() {
    const sources = currentDashboard?.sources || [];
    const timestamps = sources
        .map(source => source.updated_at ? new Date(source.updated_at) : null)
        .filter(date => date && !isNaN(date.getTime()))
        .sort((a, b) => b.getTime() - a.getTime());
    return timestamps.length ? dateTime(timestamps[0]) : text('today');
}

function analyticsInsightForCard(key, card, analytics) {
    const dashboard = currentDashboard || {};
    const summary = analytics?.summary || {};
    const kpis = dashboard.kpis || {};
    const details = analytics?.details || {};
    const balance = numberValue(kpis.balance_qty, details.daily_requirement?.balance_qty, details.output?.balance_qty);
    const output = numberValue(kpis.total_output, details.output?.total_output);
    const cardValue = `${metricValue(card)}${card.suffix ? ` ${card.suffix}` : ''}`;
    const capKeys = ['cap_data_accuracy', 'cap_output_achievement', 'cap_coverage_ready', 'cap_daily_output', 'cap_critical_order', 'cap_controlled'];

    const insightMap = {
        production_status: {
            title: text('production_status'),
            text: textTemplate('production_status_insight', {status: metricValue(card), output: fmt(Math.round(output)), balance: fmt(Math.round(balance))})
        },
        output_achievement: {
            title: text('output_achievement'),
            text: textTemplate('output_achievement_insight', {value: cardValue, output: fmt(Math.round(output))})
        },
        data_accuracy: {
            title: text('data_accuracy'),
            text: textTemplate('data_accuracy_insight', {value: cardValue})
        },
        monitoring_coverage: {
            title: text('monitoring_coverage'),
            text: textTemplate('monitoring_coverage_insight', {value: cardValue})
        },
        source_sync: {
            title: text('source_sync'),
            text: textTemplate('source_sync_insight', {value: cardValue})
        },
        data_update: {
            title: text('data_update'),
            text: textTemplate('data_update_insight', {value: latestSourceUpdateLabel()})
        },
        ready_coverage: {
            title: text('ready_coverage'),
            text: textTemplate('ready_coverage_insight', {value: cardValue, ready: fmt(Math.round(summary.total_ready || 0))})
        },
        req_daily_output: {
            title: text('req_daily_output'),
            text: textTemplate('req_daily_output_insight', {value: cardValue, balance: fmt(Math.round(balance))})
        },
        total_ready_load: {
            title: text('total_ready_load'),
            text: textTemplate('total_ready_load_insight', {value: cardValue})
        },
        avg_daily_output: {
            title: text('avg_daily_output'),
            text: textTemplate('avg_daily_output_insight', {value: cardValue})
        },
        avg_daily_capacity: {
            title: text('avg_daily_capacity'),
            text: textTemplate('avg_daily_capacity_insight', {value: cardValue})
        },
        capacity_gap: {
            title: card.label || text('capacity_gap'),
            text: textTemplate('capacity_gap_insight', {label: card.label || text('capacity_gap'), value: cardValue})
        },
        sequence_issues: {
            title: text('sequence_issues'),
            text: textTemplate('sequence_issues_insight', {value: cardValue})
        },
        critical_orders: {
            title: text('critical_orders'),
            text: textTemplate('critical_orders_insight', {value: cardValue})
        }
    };

    if (capKeys.includes(key)) {
        return {
            status: card.status || 'watch',
            title: card.label || text(key),
            text: textTemplate('cap_insight', {label: card.label || text(key)})
        };
    }

    if (!insightMap[key]) return null;
    return {
        status: card.status || 'good',
        ...insightMap[key]
    };
}

function analyticsInsightsForCards(cards, analytics) {
    const byKey = analyticsCards(analytics);
    const insights = cards
        .map(key => analyticsInsightForCard(key, byKey[key] || {}, analytics))
        .filter(Boolean);

    return insights.length ? insights : [{
        status: 'good',
        title: text('production_status'),
        text: text('analytics_ready')
    }];
}

function actionMatchesCard(item, key) {
    const title = String(item?.title || '');
    return {
        cap_data_accuracy: title === '1. Data Accuracy',
        cap_output_achievement: title === '2. Output Achievement',
        cap_coverage_ready: title === '3. Coverage Ready Load',
        cap_daily_output: title === '4. Kebutuhan Output Harian',
        cap_critical_order: title === '5. Order Delivery Kritis',
        cap_controlled: title === 'Kondisi terkendali'
    }[key] || false;
}

function openAnalyticsCardDetail(key) {
    const dashboard = currentDashboard || {};
    const analytics = dashboard.management_analytics || {};
    const details = analytics.details || {};
    const summary = analytics.summary || {};
    const dataAccuracy = analytics.data_accuracy || {};
    const cards = analyticsCards(analytics);
    const card = cards[key] || {};
    const value = `${metricValue(card)}${card.suffix ? ` ${card.suffix}` : ''}`;
    const safeRows = [];
    const internalRows = [];
    const periodRows = (dashboard.qty_pdk_vs_output || []).map(row => `
        <tr>
            ${tableCell(row.label)}
            ${tableCell(row.pdk, true)}
            ${tableCell(row.output, true)}
            ${tableCell((Number(row.pdk) || 0) - (Number(row.output) || 0), true)}
        </tr>
    `);
    const readyRows = (dashboard.ready_to_load || []).map(row => `
        <tr>
            ${tableCell(row.label)}
            ${tableCell(row.ready, true)}
        </tr>
    `);
    const capacityRows = (dashboard.output_vs_capacity || []).map(row => `
        <tr>
            ${tableCell(row.label)}
            ${tableCell(row.output, true)}
            ${tableCell(row.input, true)}
            ${tableCell(row.capacity, true)}
            ${tableCell((Number(row.capacity) || 0) - (Number(row.output) || 0), true)}
        </tr>
    `);
    const orderRows = (dashboard.top_priority_orders || []).map(row => `
        <tr>
            ${tableCell(row.order)}
            ${tableCell(row.style)}
            ${tableCell(row.delivery)}
            ${tableCell(row.qty_pdk, true)}
            ${tableCell(row.qty_ready, true)}
        </tr>
    `);

    const addRow = (rows, label, description) => rows.push(`
        <tr>
            ${tableCell(label)}
            ${tableCell(description)}
        </tr>
    `);

    if (key === 'production_status') {
        return renderDetailTable(`Detail ${card.label || text('production_status')}`, ['Order', 'Style', 'Delivery', 'QTY PDK', 'QTY Ready'], orderRows, [
            [text('card_value'), value],
            [text('formula'), text('production_status_formula')]
        ], text('display_note'));
    }

    if (key === 'output_achievement' || key === 'plan_completion') {
        return renderDetailTable(`Detail ${card.label || text('output_achievement')}`, ['Period', 'QTY PDK', 'QTY Output', 'Balance'], periodRows, [
            [text('card_value'), value],
            [text('formula'), text('output_formula')]
        ], text('detail_qty_hidden'));
    }

    if (key === 'data_accuracy' || key === 'data_reliability') {
        const rows = (dataAccuracy.periods || []).map(row => `
            <tr>
                ${tableCell(row.label)}
                ${tableCell(row.pdk, true)}
                ${tableCell(row.output, true)}
                ${tableCell(row.balance, true)}
                ${tableCell(row.ready, true)}
            </tr>
        `);
        return renderDetailTable(`Detail ${card.label || text('data_accuracy')}`, ['Period', 'PDK', 'Output', 'Balance', 'Ready'], rows, [
            [text('card_value'), value],
            [text('validation'), dataAccuracy.message || '-']
        ], text('accuracy_formula'));
    }

    if (key === 'monitoring_coverage') {
        const modules = [
            'APS JO Tracking',
            'Engage 32a Inflow',
            'Engage 32a Outflow',
            'Accessories Controlist',
            'Dashboard Analytics'
        ];
        const rows = modules.map(module => `
            <tr>
                ${tableCell(module)}
                ${tableCell(text('monitored'))}
            </tr>
        `);
        return renderDetailTable(`Detail ${card.label || text('monitoring_coverage')}`, [text('module'), text('status')], rows, [
            [text('card_value'), value]
        ], text('monitoring_note'));
    }

    if (key === 'source_sync') {
        const sources = dashboard.sources || [];
        const rows = sources.map(source => `
            <tr>
                ${tableCell(source.label || source.key || '-')}
                ${tableCell(source.exists ? text('synced') : text('missing'))}
            </tr>
        `);
        return renderDetailTable(`Detail ${card.label || text('source_sync')}`, ['Source', text('status')], rows, [
            [text('card_value'), value]
        ], text('source_sync_formula'));
    }

    if (key === 'data_update') {
        const sources = dashboard.sources || [];
        const rows = sources.map(source => `
            <tr>
                ${tableCell(source.label || source.key || '-')}
                ${tableCell(dateTime(source.updated_at))}
                ${tableCell(source.exists ? text('synced') : text('missing'))}
            </tr>
        `);
        return renderDetailTable(`Detail ${card.label || text('data_update')}`, [text('source'), text('last_update'), text('status')], rows, [
            [text('card_value'), value]
        ], text('data_update_formula'));
    }

    if (['trend', 'production_flow'].includes(key)) {
        return renderDetailTable(`Detail ${card.label || 'Analytics Card'}`, ['Order', 'Style', 'Delivery', 'QTY PDK', 'QTY Ready'], orderRows, [
            [text('card_value'), value]
        ], text('general_indicator_note'));
    }

    if (key === 'ready_coverage') {
        return renderDetailTable(`Detail ${card.label || text('ready_coverage')}`, ['Period', 'Ready Load'], readyRows, [
            [text('card_value'), value]
        ], 'Ready Coverage = Total Ready Load / Avg Daily Capacity.');
    }

    if (key === 'req_daily_output') {
        return renderDetailTable(`Detail ${card.label || text('req_daily_output')}`, ['Period', 'QTY PDK', 'QTY Output', 'Balance'], periodRows, [
            [text('card_value'), value],
            ['Export Days Left', `${fmt(Number(details.daily_requirement?.period_days_left || 0).toFixed(1))} ${text('days')}`]
        ], 'Required Daily Output = balance delivery aktif / sisa hari kerja export.');
    }

    if (['total_ready_load', 'avg_daily_output', 'avg_daily_capacity', 'capacity_gap', 'sequence_issues', 'critical_orders'].includes(key)) {
        if (key === 'total_ready_load') {
            return renderDetailTable(`Detail ${card.label}`, ['Period', 'Ready Load'], readyRows, [[text('card_value'), value]], 'Total Ready Load = total ready dari periode yang tampil di dashboard.');
        }
        if (['avg_daily_output', 'avg_daily_capacity', 'capacity_gap'].includes(key)) {
            return renderDetailTable(`Detail ${card.label}`, ['Hari', 'Output', 'Input', 'Capacity', 'Gap'], capacityRows, [[text('card_value'), value]], 'Data berasal dari grafik kapasitas vs output vs input.');
        }
        if (key === 'critical_orders') {
            return renderDetailTable(`Detail ${card.label}`, ['Order', 'Style', 'Delivery', 'QTY PDK', 'QTY Ready'], orderRows, [[text('card_value'), value]], 'Critical Orders dihitung dari order prioritas dalam horizon delivery.');
        }
        return renderDetailTable(`Detail ${card.label}`, ['Period', 'PDK', 'Output', 'Balance', 'Ready'], (dataAccuracy.periods || []).map(row => `
            <tr>
                ${tableCell(row.label)}
                ${tableCell(row.pdk, true)}
                ${tableCell(row.output, true)}
                ${tableCell(row.balance, true)}
                ${tableCell(row.ready, true)}
            </tr>
        `), [[text('card_value'), value]], 'Sequence Issues berasal dari validasi urutan periode dashboard.');
    }

    if (key.startsWith('cap_')) {
        const action = (analytics.action_plan || []).find(item => actionMatchesCard(item, key));
        const rows = action ? [
            action.masalah ? `<tr>${tableCell('Masalah')}${tableCell(action.masalah)}</tr>` : '',
            action.penyebab ? `<tr>${tableCell('Penyebab')}${tableCell(action.penyebab)}</tr>` : '',
            action.prevention ? `<tr>${tableCell('Pencegahan')}${tableCell(action.prevention)}</tr>` : '',
            action.handling ? `<tr>${tableCell('Penanganan')}${tableCell(action.handling)}</tr>` : ''
        ].filter(Boolean) : [];
        return renderDetailTable(`Detail ${card.label || 'CAP'}`, ['Item', 'Keterangan'], rows, [
            ['Status', action?.status || '-']
        ], 'CAP berisi masalah, penyebab, prevention, dan handling dari data dashboard.');
    }

    renderDetailTable('Detail Analytics Card', ['Item', 'Keterangan'], safeRows, [
        ['Nilai', value || '-']
    ], 'Detail card belum tersedia.');
}

function renderAnalytics(analytics) {
    const condition = analytics?.overall_condition || {};
    const metrics = analytics?.metrics || [];
    const insights = analytics?.insights || [];
    const summary = analytics?.summary || {};
    const details = analytics?.details || {};
    const dataAccuracy = analytics?.data_accuracy || {};
    const accuracyIssues = dataAccuracy.issues || [];
    const actions = analytics?.action_plan || [];
    const capacityGap = Number(summary.capacity_gap || 0);
    const capacityGapLabel = capacityGap < 0 ? 'Capacity Surplus' : 'Capacity Gap';
    const selectedCards = getVisibleAnalyticsCards();
    const selectedCapCards = selectedCards.filter(key => key.startsWith('cap_'));
    const visibleActions = featureVisibility.internalAnalytics
        ? actions
        : actions.filter(item => selectedCapCards.some(key => actionMatchesCard(item, key)));
    renderAppLanguage();
    document.getElementById('analyticsTitle').textContent = text('analyticsTitle');
    document.getElementById('analyticsInsightTitle').textContent = text('insightTitle');
    document.getElementById('analyticView').classList.toggle('show-actions', visibleActions.length > 0);

    const conditionCard = document.getElementById('overallCondition');
    conditionCard.className = `box condition-card simple ${featureVisibility.internalAnalytics ? esc(condition.status || '') : 'good'}`;
    document.querySelector('#overallCondition h2').textContent = featureVisibility.internalAnalytics ? (condition.title || '-') : text('production_status_label');
    document.getElementById('conditionLevel').textContent = featureVisibility.internalAnalytics ? (condition.level || '-') : text('on_track');
    document.querySelector('#conditionShortage b').textContent = featureVisibility.internalAnalytics ? fmt(Math.round(details.output?.balance_qty || 0)) : '-';
    document.getElementById('conditionSummary').textContent = '';
    document.getElementById('conditionMeta').textContent = '';

    const allCards = analyticsCards(analytics);
    const visibleMetrics = featureVisibility.internalAnalytics
        ? metrics
        : selectedCards.map(key => allCards[key]).filter(item => item && item.label);
    const visibleInsights = featureVisibility.internalAnalytics
        ? insights
        : analyticsInsightsForCards(selectedCards, analytics);

    document.getElementById('analyticsMetrics').innerHTML = visibleMetrics.map(item => `
        <div class="metric ${esc(item.status || '')}">
            <span>${esc(item.label)}</span>
            <strong>${metricValue(item)} <small>${esc(item.suffix)}</small></strong>
            <button type="button" class="detail-btn" onclick="openAnalyticsCardDetail('${esc(item.key || '')}')">${esc(text('detail'))}</button>
        </div>
    `).join('');

    document.getElementById('analyticsInsights').innerHTML = visibleInsights.map(item => `
        <div class="insight ${esc(item.status || '')}">
            <i class="badge"></i>
            <div>
                <b>${esc(item.title)}</b>
                <p>${esc(item.text)}</p>
            </div>
        </div>
    `).join('');

    const summaryCards = [
        [text('total_ready_load'), `${fmt(Math.round(summary.total_ready || 0))} ${text('pcs')}`, [`${text('ready_periods')}: ${(details.ready?.periods || []).map(row => `${row.label} ${fmt(Math.round(row.ready || 0))} ${text('pcs').toLowerCase()}`).join(', ') || '-'}`], 'ready'],
        [text('avg_daily_output'), `${fmt(Math.round(summary.avg_daily_output || 0))} ${text('pcs')}`, [`${text('required_daily_output_label')}: ${fmt(Math.round(details.daily_requirement?.required_daily_output || 0))} ${text('pcs')}/day`], 'daily'],
        [text('avg_daily_capacity'), `${fmt(Math.round(summary.avg_daily_capacity || 0))} ${text('pcs')}`, ['Capacity = total balance delivery aktif / total sisa hari MID + END.'], 'daily'],
        [capacityGap < 0 ? text('capacity_surplus') : text('capacity_gap'), `${fmt(Math.round(Math.abs(capacityGap)))} ${text('pcs')}`, ['Selisih total kapasitas dengan output harian.'], 'daily'],
        [text('data_accuracy'), `${fmt(Math.round(summary.data_accuracy_score || 0))}%`, accuracyIssues.map(item => `${item.title}: ${item.text}`), 'accuracy'],
        [text('sequence_issues'), `${fmt(Math.round(summary.sequence_issues || 0))} ${text('issue')}`, accuracyIssues.map(item => `${item.title}: ${item.text}`), 'accuracy']
    ];

    if (featureVisibility.criticalOrders && featureVisibility.internalAnalytics) {
        summaryCards.splice(4, 0, [text('critical_orders'), `${fmt(Math.round(summary.critical_orders || 0))} ${text('order')}`, (details.priority?.orders || []).slice(0, 3).map(row => `${row.order} ${row.delivery}: ${fmt(Math.round(row.qty_ready || 0))} ${text('pcs').toLowerCase()}`), 'critical']);
    }

    document.getElementById('analyticsSummary').innerHTML = summaryCards.map((item, index) => `
        <div class="summary-item">
            <span>${esc(item[0])}</span>
            <strong>${esc(item[1])}</strong>
            <button type="button" class="detail-btn" onclick="openAnalyticsDetail('${esc(item[3])}')">${esc(text('detail'))}</button>
        </div>
    `).join('');

    document.getElementById('managementActions').innerHTML = visibleActions.map(item => {
        const hasStructured = item.masalah !== undefined || item.penyebab !== undefined;
        const capRows = hasStructured ? [
            item.masalah    ? `<div class="cap-row"><span class="cap-label masalah">${esc(text('problem'))} :</span><span class="cap-val">${esc(item.masalah)}</span></div>` : '',
            item.penyebab   ? `<div class="cap-row"><span class="cap-label penyebab">${esc(text('cause'))} :</span><span class="cap-val">${esc(item.penyebab)}</span></div>` : '',
            item.prevention ? `<div class="cap-row"><span class="cap-label pencegahan">${esc(text('prevention'))} :</span><span class="cap-val">${esc(item.prevention)}</span></div>` : '',
            item.handling   ? `<div class="cap-row"><span class="cap-label penanganan">${esc(text('handling'))} :</span><span class="cap-val">${esc(item.handling)}</span></div>` : '',
        ].join('') : [
            item.result     ? `<p><strong>${esc(text('result'))}:</strong> ${esc(item.result)}</p>` : '',
            item.prevention ? `<p><strong>${esc(text('prevention'))}:</strong> ${esc(item.prevention)}</p>` : '',
            item.handling   ? `<p><strong>${esc(text('handling'))}:</strong> ${esc(item.handling)}</p>` : '',
        ].join('');
        return `
        <div class="action-row ${esc(item.status || '')}">
            <i class="badge"></i>
            <div>
                <b>${esc(item.title)}</b>
                ${capRows}
            </div>
        </div>`;
    }).join('');

    document.getElementById('dataAccuracyRows').innerHTML = (accuracyIssues.length ? accuracyIssues : [{
        status: dataAccuracy.status || 'good',
        title: text('valid_sequence'),
        text: dataAccuracy.message || text('valid_sequence_text')
    }]).map((item, index) => `
        <div class="accuracy-row ${esc(item.status || '')}">
            <i class="badge"></i>
            <div>
                <b>${esc(item.title)}</b>
                <p>${esc(item.text)}</p>
            </div>
        </div>
    `).join('');
}

function renderAnalyticsCardOptions() {
    const selected = new Set(getVisibleAnalyticsCards());
    document.getElementById('analyticsMenuTitle').textContent = text('analyticsDisplay');
    document.getElementById('analyticsCardOptions').innerHTML = analyticsCardDefinitions.map(item => `
        <label class="analytics-card-option">
            <input type="checkbox" value="${esc(item.key)}" ${selected.has(item.key) ? 'checked' : ''}>
            <div>${esc(text(item.key))}<span>${esc(text(item.note))}</span></div>
        </label>
    `).join('');
}

function openAnalyticsMenu() {
    renderAnalyticsCardOptions();
    document.getElementById('analyticsMenuMessage').textContent = '';
    const modal = document.getElementById('analyticsMenuModal');
    modal.classList.add('open');
    modal.style.display = 'flex';
}

function closeAnalyticsMenu() {
    const modal = document.getElementById('analyticsMenuModal');
    modal.classList.remove('open');
    modal.style.display = 'none';
}

function closeAnalyticsMenuAndLogout() {
    closeAnalyticsMenu();
    if (calendarAuthenticated) {
        logoutCalendar();
    }
}

function openManagementModal() {
    renderDeliveryToggle();
    const modal = document.getElementById('managementModal');
    modal.classList.add('open');
    modal.style.display = 'flex';
}

function closeManagementModal() {
    const modal = document.getElementById('managementModal');
    modal.classList.remove('open');
    modal.style.display = 'none';
    if (calendarAuthenticated) {
        logoutCalendar();
    }
}

function ensureManagementAccess(action) {
    if (calendarAuthenticated) {
        action();
        return;
    }
    pendingLoginAction = action;
    openCalendarLoginModal();
}

function saveAnalyticsMenu() {
    const selected = Array.from(document.querySelectorAll('#analyticsCardOptions input:checked')).map(input => input.value);
    if (!selected.length) {
        document.getElementById('analyticsMenuMessage').textContent = text('choose_one_card');
        return;
    }
    setVisibleAnalyticsCards(selected);
    if (currentDashboard?.management_analytics) {
        renderAnalytics(currentDashboard.management_analytics);
    }
    closeAnalyticsMenuAndLogout();
}

function resetAnalyticsMenu() {
    setVisibleAnalyticsCards(defaultAnalyticsCards);
    renderAnalyticsCardOptions();
    if (currentDashboard?.management_analytics) {
        renderAnalytics(currentDashboard.management_analytics);
    }
}

function renderDashboardCharts(dashboard) {
    renderGroupedChart(document.getElementById('qtyPdkOutputChart'), dashboard.qty_pdk_vs_output || [], ['pdk', 'output']);
    renderGroupedChart(document.getElementById('readyToLoadChart'), dashboard.ready_to_load || [], ['ready']);
    renderCapacityChart(document.getElementById('outputCapacityChart'), dashboard.output_vs_capacity || []);
}

function renderLastUpdateList(dashboard, serverTime) {
    const capacityRows = (dashboard.output_vs_capacity || []).slice(-5).reverse();
    document.getElementById('lastUpdateRows').innerHTML = capacityRows.length ? `
        <div class="db-last-list">
            ${capacityRows.map(row => {
                const output = Number(row.output) || 0;
                const input = Number(row.input) || 0;
                const capacity = Number(row.capacity) || 0;
                const gap = capacity - output;
                return `
                <div class="db-last-item">
                    <b>${esc(row.label || '-')}</b>
                    <p>${esc(text('capacity'))}: ${fmt(Math.round(capacity))} ${esc(text('pcs').toLowerCase())}</p>
                    <p>${esc(text('input_32a'))}: ${fmt(Math.round(input))} ${esc(text('pcs').toLowerCase())}</p>
                    <p>${esc(text('output'))}: ${fmt(Math.round(output))} ${esc(text('pcs').toLowerCase())}</p>
                    <p>${gap >= 0 ? esc(text('gap')) : esc(text('surplus'))}: ${fmt(Math.round(Math.abs(gap)))} ${esc(text('pcs').toLowerCase())}</p>
                </div>
            `}).join('')}
        </div>
    ` : `<div class="db-last-empty">${esc(text('no_capacity_data'))}</div>`;
}

function renderBalanceBreakdown(rows) {
    const items = (rows || [])
        .map(row => ({
            label: row.label || '-',
            balance: Math.max(0, (Number(row.pdk) || 0) - (Number(row.output) || 0))
        }))
        .filter(row => row.balance > 0)
        .slice(0, 4);

    document.getElementById('balanceBreakdown').innerHTML = items.length ? items.map(row => `
        <div class="kpi-balance-row">
            <span>${esc(row.label)}</span>
            <span>:</span>
            <b>${fmt(Math.round(row.balance))}</b>
            <small>${esc(text('pcs'))}</small>
        </div>
    `).join('') : `<div class="db-last-empty">${esc(text('no_short_qty'))}</div>`;
}

function calendarPeriods() {
    const dashboard = currentDashboard || {};
    const periods = dashboard.qty_pdk_vs_output || [];
    const currentCalendar = dashboard.management_analytics?.details?.daily_requirement?.period_calendar || {};
    const dates = [];

    periods.forEach(row => {
        const label = String(row.label || '');
        const match = label.match(/^(MID|END)\s+([A-Za-z]+)$/i);
        if (!match) return;
        const monthIndex = ['jan','feb','mar','apr','may','june','july','aug','sept','oct','nov','dec'].indexOf(match[2].toLowerCase());
        if (monthIndex >= 0) {
            dates.push(new Date(new Date().getFullYear(), monthIndex, 1));
        }
    });

    if (currentCalendar.start_date) {
        dates.push(new Date(`${currentCalendar.start_date}T00:00:00`));
    }

    return dates;
}

function isoDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function periodDateRange(label) {
    const match = String(label || '').match(/^(MID|END)\s+([A-Za-z]+)$/i);
    if (!match) return null;

    const monthIndex = ['jan','feb','mar','apr','may','june','july','aug','sept','oct','nov','dec'].indexOf(match[2].toLowerCase());
    if (monthIndex < 0) return null;

    const year = new Date().getFullYear();
    const startDay = match[1].toUpperCase() === 'MID' ? 1 : 16;
    const endDay = match[1].toUpperCase() === 'MID' ? 15 : new Date(year, monthIndex + 1, 0).getDate();
    return {
        start: new Date(year, monthIndex, startDay),
        end: new Date(year, monthIndex, endDay)
    };
}

function calendarWorkdayValue(date) {
    const iso = isoDate(date);
    if (selectedHolidays.has(iso)) return 0;
    if (selectedHalfDays.has(iso)) return 0.5;
    if (selectedQuarterDays.has(iso)) return 0.25;
    if (date.getDay() === 0 && !selectedWorkDays.has(iso)) return 0;
    return 1;
}

function calendarWorkdaysForLabel(label) {
    const range = periodDateRange(label);
    if (!range) return null;

    let total = 0;
    const cursor = new Date(range.start);
    while (cursor <= range.end) {
        total += calendarWorkdayValue(cursor);
        cursor.setDate(cursor.getDate() + 1);
    }

    return total;
}

function renderCalendar() {
    const monthStart = new Date(calendarCursor.getFullYear(), calendarCursor.getMonth(), 1);
    const first = new Date(monthStart);
    first.setDate(first.getDate() - first.getDay());
    const title = new Intl.DateTimeFormat(analyticsLanguage === 'en' ? 'en-US' : 'id-ID', {month:'long', year:'numeric'}).format(monthStart);
    const today = isoDate(new Date());
    const dayNames = analyticsLanguage === 'en'
        ? ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
        : ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    const cells = [];

    for (let i = 0; i < 42; i++) {
        const date = new Date(first);
        date.setDate(first.getDate() + i);
        const iso = isoDate(date);
        const isCurrentMonth = date.getMonth() === monthStart.getMonth();
        const isSunday = date.getDay() === 0;
        const isHoliday = selectedHolidays.has(iso);
        const isHalf = selectedHalfDays.has(iso);
        const isQuarter = selectedQuarterDays.has(iso);
        const isWork = selectedWorkDays.has(iso);
        const label = isHoliday ? text('holiday') : (isQuarter ? text('quarter_day') : (isHalf ? text('half_day') : (isWork ? text('workday') : (isSunday ? text('sunday_off') : text('workday')))));
        cells.push(`
            <button type="button" class="calendar-day ${isCurrentMonth ? '' : 'out'} ${isSunday ? 'sunday' : ''} ${isWork ? 'work' : ''} ${isHalf ? 'half' : ''} ${isQuarter ? 'quarter' : ''} ${isHoliday ? 'holiday' : ''} ${iso === today ? 'today' : ''}" data-date="${iso}">
                <b>${date.getDate()}</b>
                <span>${label}</span>
            </button>
        `);
    }

    document.getElementById('calendarTitle').textContent = title;
    renderCalendarSummary();
    document.getElementById('calendarRows').innerHTML = dayNames.map(day => `<div class="calendar-head">${day}</div>`).join('') + cells.join('');
    document.querySelectorAll('#calendarRows .calendar-day').forEach(button => {
        button.addEventListener('click', () => {
            const date = button.dataset.date;
            cycleCalendarDay(date);
            renderCalendar();
        });
    });
}

function renderCalendarSummary() {
    const items = currentDashboard?.delivery_workdays || [];
    document.getElementById('calendarSummary').innerHTML = items.length ? items.slice(0, 4).map(item => `
        <div class="calendar-summary-item">
            <span>${esc(item.label)}</span>
            <b>${fmt(calendarWorkdaysForLabel(item.label) ?? item.total_workdays)} ${esc(text('days'))}</b>
        </div>
    `).join('') : '';
}

function cycleCalendarDay(date) {
    const day = new Date(`${date}T00:00:00`).getDay();
    const isSunday = day === 0;
    const isHoliday = selectedHolidays.has(date);
    const isHalf = selectedHalfDays.has(date);
    const isQuarter = selectedQuarterDays.has(date);
    const isWork = selectedWorkDays.has(date);

    selectedHolidays.delete(date);
    selectedHalfDays.delete(date);
    selectedQuarterDays.delete(date);
    selectedWorkDays.delete(date);

    if (isSunday) {
        if (!isWork && !isHalf && !isQuarter && !isHoliday) {
            selectedWorkDays.add(date);
        } else if (isWork) {
            selectedHalfDays.add(date);
        } else if (isHalf) {
            selectedQuarterDays.add(date);
        } else if (isQuarter) {
            selectedHolidays.add(date);
        }
        return;
    }

    if (!isHalf && !isQuarter && !isHoliday) {
        selectedHalfDays.add(date);
    } else if (isHalf) {
        selectedQuarterDays.add(date);
    } else if (isQuarter) {
        selectedHolidays.add(date);
    }
}

function openWorkdayModal() {
    const periods = calendarPeriods();
    if (periods.length) {
        calendarCursor = new Date(periods[0].getFullYear(), periods[0].getMonth(), 1);
    }
    const settings = currentDashboard?.holiday_settings || {};
    selectedHolidays = new Set(Array.isArray(settings) ? settings : (settings.holidays || []));
    selectedHalfDays = new Set(Array.isArray(settings) ? [] : (settings.half_days || []));
    selectedQuarterDays = new Set(Array.isArray(settings) ? [] : (settings.quarter_days || []));
    selectedWorkDays = new Set(Array.isArray(settings) ? [] : (settings.work_days || []));
    document.getElementById('workdayMessage').textContent = '';
    renderCalendar();

    const modal = document.getElementById('workdayModal');
    modal.classList.add('open');
    modal.style.display = 'flex';
}

function closeWorkdayModal() {
    const modal = document.getElementById('workdayModal');
    modal.classList.remove('open');
    modal.style.display = 'none';
}

function closeWorkdayModalAndLogout() {
    closeWorkdayModal();
    if (calendarAuthenticated) {
        logoutCalendar();
    }
}

function openCalendarLoginModal() {
    document.getElementById('calendarLoginMessage').textContent = '';
    document.getElementById('calendarUsername').value = '';
    document.getElementById('calendarPassword').value = '';
    document.getElementById('calendarLoginTitle').textContent = pendingLoginAction ? text('login_management') : text('login_calendar');
    const modal = document.getElementById('calendarLoginModal');
    modal.classList.add('open');
    modal.style.display = 'flex';
    setTimeout(() => document.getElementById('calendarUsername').focus(), 0);
}

function closeCalendarLoginModal() {
    const modal = document.getElementById('calendarLoginModal');
    modal.classList.remove('open');
    modal.style.display = 'none';
}

async function loginCalendar() {
    const button = document.getElementById('calendarLoginSubmit');
    const message = document.getElementById('calendarLoginMessage');
    const username = document.getElementById('calendarUsername').value;
    const password = document.getElementById('calendarPassword').value;

    button.disabled = true;
    message.textContent = text('logging_in');

    try {
        const response = await fetch(urls.calendarLogin, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({username, password})
        });
        const result = await response.json();
        if (!response.ok || !result.ok) {
            throw new Error(result.message || text('login_failed'));
        }
        calendarAuthenticated = true;
        closeCalendarLoginModal();
        const action = pendingLoginAction;
        pendingLoginAction = null;
        if (typeof action === 'function') {
            action();
        } else {
            openManagementModal();
        }
    } catch (error) {
        message.textContent = error.message || text('login_failed');
    } finally {
        button.disabled = false;
    }
}

async function saveWorkdays() {
    if (!calendarAuthenticated) {
        closeWorkdayModal();
        openCalendarLoginModal();
        return;
    }

    const button = document.getElementById('workdaySave');
    const message = document.getElementById('workdayMessage');
    const holidays = Array.from(selectedHolidays).sort();
    const halfDays = Array.from(selectedHalfDays).sort();
    const quarterDays = Array.from(selectedQuarterDays).sort();
    const workDays = Array.from(selectedWorkDays).sort();

    button.disabled = true;
    message.textContent = text('saving');

    try {
        const response = await fetch(urls.saveWorkdays, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({holidays, half_days: halfDays, quarter_days: quarterDays, work_days: workDays})
        });
        const result = await response.json();
        if (!response.ok || !result.ok) {
            if (response.status === 401) {
                calendarAuthenticated = false;
            }
            throw new Error(result.message || text('save_failed'));
        }
        message.textContent = result.message || text('saved');
        await loadStatus();
        if (calendarAuthenticated) {
            logoutCalendar();
        }
        closeWorkdayModal();
    } catch (error) {
        message.textContent = error.message || text('save_failed');
    } finally {
        button.disabled = false;
    }
}

async function logoutCalendar() {
    calendarAuthenticated = false;
    try {
        await fetch(urls.calendarLogout, {method: 'POST'});
    } catch (error) {
        // Login kalender tetap dianggap selesai di browser setelah submit.
    }
}

function setActiveView(name) {
    document.querySelectorAll('.menu button').forEach(button => button.classList.toggle('active', button.dataset.view === name));
    document.getElementById('dashboardView').classList.toggle('active', name === 'dashboard');
    document.getElementById('analyticView').classList.toggle('active', name === 'analytic');

    if (name === 'dashboard' && currentDashboard) {
        renderDashboardCharts(currentDashboard);
    }
}

function render(data) {
    calendarAuthenticated = !!data.calendar_authenticated;
    const dashboard = data.dashboard_data;
    if (!dashboard || !dashboard.available) {
        renderEmpty(dashboard);
        return;
    }

    currentDashboard = dashboard;
    renderAppLanguage();
    const analyticsDetails = dashboard.management_analytics?.details || {};
    const balanceQty = numberValue(
        dashboard.kpis?.balance_qty,
        analyticsDetails.daily_requirement?.balance_qty,
        analyticsDetails.output?.balance_qty
    );
    const latestCapacity = (dashboard.output_vs_capacity || []).slice(-1)[0] || {};
    document.getElementById('lastUpdate').textContent = `*${text('last_update')} : ${latestCapacity.label || '-'}`;
    renderLastUpdateList(dashboard, data.server_time);
    document.getElementById('totalOutput').textContent = fmt(Math.round(numberValue(dashboard.kpis?.total_output)));
    document.getElementById('balanceQty').textContent = fmt(Math.round(balanceQty));
    renderBalanceBreakdown(dashboard.balance_breakdown || dashboard.qty_pdk_vs_output);

    if (document.getElementById('dashboardView').classList.contains('active')) {
        renderDashboardCharts(dashboard);
    }
    renderAnalytics(dashboard.management_analytics);

    document.getElementById('priorityRows').innerHTML = (dashboard.top_priority_orders || []).map((row, index) => `
        <tr>
            <td>${index + 1}.</td>
            <td class="order">${esc(row.order)}</td>
            <td class="style">${esc(row.style)}</td>
            <td class="delivery">${esc(shortDelivery(row.delivery))}</td>
            <td class="num">${fmt(Math.round(row.qty_ready))}</td>
            <td class="unit">${esc(text('pcs'))}</td>
        </tr>
    `).join('');
}

function refreshLanguage() {
    renderAppLanguage();
    renderDeliveryToggle();
    if (!currentDashboard) return;
    const latestCapacity = (currentDashboard.output_vs_capacity || []).slice(-1)[0] || {};
    document.getElementById('lastUpdate').textContent = `*${text('last_update')} : ${latestCapacity.label || '-'}`;
    renderLastUpdateList(currentDashboard);
    renderBalanceBreakdown(currentDashboard.balance_breakdown || currentDashboard.qty_pdk_vs_output);
    if (document.getElementById('dashboardView').classList.contains('active')) {
        renderDashboardCharts(currentDashboard);
    }
    if (document.getElementById('workdayModal').classList.contains('open')) {
        renderCalendar();
    }
    if (currentDashboard.management_analytics) {
        renderAnalytics(currentDashboard.management_analytics);
    }
    document.getElementById('priorityRows').innerHTML = (currentDashboard.top_priority_orders || []).map((row, index) => `
        <tr>
            <td>${index + 1}.</td>
            <td class="order">${esc(row.order)}</td>
            <td class="style">${esc(row.style)}</td>
            <td class="delivery">${esc(shortDelivery(row.delivery))}</td>
            <td class="num">${fmt(Math.round(row.qty_ready))}</td>
            <td class="unit">${esc(text('pcs'))}</td>
        </tr>
    `).join('');
}

async function loadStatus() {
    const statusUrl = `${urls.status}?delivery_count=${encodeURIComponent(selectedDeliveryCount)}`;
    const response = await fetch(statusUrl, {cache:'no-store'});
    render(await response.json());
}

function renderDeliveryToggle() {
    document.querySelectorAll('#managementDeliveryToggle button').forEach(button => {
        button.classList.toggle('active', Number(button.dataset.deliveryCount) === selectedDeliveryCount);
    });
}

document.querySelectorAll('.menu button').forEach(button => {
    button.addEventListener('click', () => setActiveView(button.dataset.view));
});

document.querySelectorAll('[data-analytics-lang]').forEach(button => {
    button.addEventListener('click', () => {
        analyticsLanguage = button.dataset.analyticsLang === 'en' ? 'en' : 'id';
        localStorage.setItem(analyticsLangStorageKey, analyticsLanguage);
        refreshLanguage();
    });
});

renderAppLanguage();
renderDeliveryToggle();

document.getElementById('lastUpdate').addEventListener('click', event => {
    event.stopPropagation();
    document.getElementById('lastUpdateBox').classList.toggle('open');
});

document.addEventListener('click', event => {
    if (!event.target.closest('#lastUpdateBox')) {
        document.getElementById('lastUpdateBox').classList.remove('open');
    }
});

document.getElementById('detailModalClose').addEventListener('click', () => {
    closeDetailModal();
});

document.getElementById('detailModal').addEventListener('click', event => {
    if (event.target.id === 'detailModal') {
        closeDetailModal();
    }
});

document.getElementById('analyticsSecret').addEventListener('click', () => {
    if (calendarAuthenticated) {
        openAnalyticsMenu();
    } else {
        pendingLoginAction = openAnalyticsMenu;
        openCalendarLoginModal();
    }
});

document.getElementById('analyticsTune').addEventListener('click', () => {
    if (calendarAuthenticated) {
        openManagementModal();
    } else {
        pendingLoginAction = openManagementModal;
        openCalendarLoginModal();
    }
});

document.getElementById('analyticsMenuClose').addEventListener('click', () => {
    closeAnalyticsMenuAndLogout();
});

document.getElementById('analyticsMenuCancel').addEventListener('click', () => {
    closeAnalyticsMenuAndLogout();
});

document.getElementById('analyticsMenuSave').addEventListener('click', () => {
    saveAnalyticsMenu();
});

document.getElementById('analyticsMenuReset').addEventListener('click', () => {
    resetAnalyticsMenu();
});

document.getElementById('analyticsMenuModal').addEventListener('click', event => {
    if (event.target.id === 'analyticsMenuModal') {
        closeAnalyticsMenuAndLogout();
    }
});

document.getElementById('workdayModalClose').addEventListener('click', () => {
    closeWorkdayModalAndLogout();
});

document.getElementById('workdayCancel').addEventListener('click', () => {
    closeWorkdayModalAndLogout();
});

document.getElementById('workdaySave').addEventListener('click', () => {
    saveWorkdays();
});

document.getElementById('calendarPrev').addEventListener('click', () => {
    calendarCursor = new Date(calendarCursor.getFullYear(), calendarCursor.getMonth() - 1, 1);
    renderCalendar();
});

document.getElementById('calendarNext').addEventListener('click', () => {
    calendarCursor = new Date(calendarCursor.getFullYear(), calendarCursor.getMonth() + 1, 1);
    renderCalendar();
});

document.getElementById('workdayModal').addEventListener('click', event => {
    if (event.target.id === 'workdayModal') {
        closeWorkdayModalAndLogout();
    }
});

document.getElementById('managementModalClose').addEventListener('click', () => {
    closeManagementModal();
});

document.getElementById('managementCalendarOpen').addEventListener('click', () => {
    openWorkdayModal();
});

document.getElementById('managementQtyOpen').addEventListener('click', () => {
    openQtyHistoryModal();
});

document.getElementById('managementAnalyticsOpen').addEventListener('click', () => {
    openAnalyticsMenu();
});

document.querySelectorAll('#managementDeliveryToggle button').forEach(button => {
    button.addEventListener('click', async () => {
        selectedDeliveryCount = Number(button.dataset.deliveryCount) === 2 ? 2 : 4;
        localStorage.setItem('heatDeliveryCount', selectedDeliveryCount);
        renderDeliveryToggle();
        await loadStatus();
    });
});

document.getElementById('managementModal').addEventListener('click', event => {
    if (event.target.id === 'managementModal') {
        closeManagementModal();
    }
});

document.getElementById('calendarLoginClose').addEventListener('click', () => {
    pendingLoginAction = null;
    closeCalendarLoginModal();
});

document.getElementById('calendarLoginCancel').addEventListener('click', () => {
    pendingLoginAction = null;
    closeCalendarLoginModal();
});

document.getElementById('calendarLoginSubmit').addEventListener('click', () => {
    loginCalendar();
});

document.getElementById('calendarPassword').addEventListener('keydown', event => {
    if (event.key === 'Enter') {
        loginCalendar();
    }
});

document.getElementById('calendarLoginModal').addEventListener('click', event => {
    if (event.target.id === 'calendarLoginModal') {
        pendingLoginAction = null;
        closeCalendarLoginModal();
    }
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        closeDetailModal();
        closeAnalyticsMenuAndLogout();
        closeWorkdayModalAndLogout();
        closeManagementModal();
        closeCalendarLoginModal();
        closeQtyHistoryModal();
    }
});

// ---- QTY History ----
async function loadQtyHistory() {
    const tableBody = document.getElementById('qtyHistoryRows');
    const emptyMessage = document.getElementById('qtyHistoryEmpty');
    const summaryGrid = document.getElementById('qtyHistorySummary');
    
    try {
        const response = await fetch(`${urls.qtyHistory}?delivery_count=${encodeURIComponent(selectedDeliveryCount)}`, {cache:'no-store'});
        const data = await response.json();
        
        if (!data.ok || !Array.isArray(data.data) || !data.data.length) {
            tableBody.innerHTML = '';
            summaryGrid.innerHTML = '';
            emptyMessage.style.display = 'block';
            return;
        }
        
        emptyMessage.style.display = 'none';
        
        // Calculate summary
        const totalPdk = data.data.reduce((sum, row) => sum + (Number(row.qty_pdk) || 0), 0);
        const totalOutput = data.data.reduce((sum, row) => sum + (Number(row.qty_output) || 0), 0);
        const lastRow = data.data[data.data.length - 1];
        const lastBalance = Number(lastRow?.balance_qty) || 0;
        
        summaryGrid.innerHTML = `
            <div class="calendar-summary-item"><span>Total QTY PDK</span><b>${fmt(Math.round(totalPdk))}</b></div>
            <div class="calendar-summary-item"><span>Total QTY Output</span><b>${fmt(Math.round(totalOutput))}</b></div>
            <div class="calendar-summary-item"><span>Balance Terakhir</span><b>${fmt(Math.round(lastBalance))}</b></div>
            <div class="calendar-summary-item"><span>Total Hari</span><b>${fmt(data.data.length)} Hari</b></div>
        `;
        
        // Render rows (newest first)
        const rows = [...data.data].reverse().map(row => {
            const tanggal = row.tanggal || row.date || '-';
            const qtyPdk = Number(row.qty_pdk) || 0;
            const qtyOutput = Number(row.qty_output) || 0;
            const balance = Number(row.balance_qty) || 0;
            const catatan = row.catatan || row.notes || '';
            const dateLabel = tanggal.length === 10 ? dateOnly(tanggal) : esc(tanggal);
            const statusClass = balance < 0 ? 'risk' : (balance === 0 ? 'good' : 'watch');
            return `
                <tr>
                    <td style="padding:8px 10px;text-align:center;font-weight:800;">${dateLabel}</td>
                    <td style="padding:8px 10px;text-align:right;font-weight:850;">${fmt(Math.round(qtyPdk))}</td>
                    <td style="padding:8px 10px;text-align:right;font-weight:850;">${fmt(Math.round(qtyOutput))}</td>
                    <td style="padding:8px 10px;text-align:right;font-weight:850;color:${balance < 0 ? 'var(--risk)' : (balance === 0 ? 'var(--ok)' : 'var(--warn)')};">${fmt(Math.round(balance))}</td>
                    <td style="padding:8px 10px;text-align:center;color:var(--muted);font-weight:750;">${esc(catatan) || '-'}</td>
                </tr>
            `;
        });
        
        tableBody.innerHTML = rows.join('');
    } catch (error) {
        tableBody.innerHTML = '';
        emptyMessage.style.display = 'block';
        emptyMessage.textContent = 'Gagal memuat data riwayat.';
    }
}

function openQtyHistoryModal() {
    const modal = document.getElementById('qtyHistoryModal');
    modal.classList.add('open');
    modal.style.display = 'flex';
    loadQtyHistory();
}

function closeQtyHistoryModal() {
    const modal = document.getElementById('qtyHistoryModal');
    modal.classList.remove('open');
    modal.style.display = 'none';
    if (calendarAuthenticated) {
        logoutCalendar();
    }
}

document.getElementById('qtyHistoryModalClose').addEventListener('click', () => {
    closeQtyHistoryModal();
});

document.getElementById('qtyHistoryRefresh').addEventListener('click', () => {
    loadQtyHistory();
});

document.getElementById('qtyHistoryModal').addEventListener('click', event => {
    if (event.target.id === 'qtyHistoryModal') {
        closeQtyHistoryModal();
    }
});

loadStatus();
setInterval(loadStatus, 60 * 60 * 1000);
</script>
</body>
</html>
