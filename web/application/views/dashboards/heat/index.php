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
        .top{display:grid;grid-template-columns:minmax(640px,38vw) auto auto 1fr;align-items:center;gap:10px}
        .title{height:34px;border:1px solid rgba(219,228,238,.9);border-radius:7px;background:rgba(255,255,255,.92);box-shadow:var(--shadow);display:flex;align-items:center;justify-content:center;font-size:clamp(18px,1.22vw,24px);font-weight:850;letter-spacing:.3px;color:var(--ink);white-space:nowrap;overflow:hidden}
        .menu{height:34px;border:1px solid rgba(219,228,238,.9);border-radius:7px;background:rgba(255,255,255,.78);box-shadow:var(--shadow);display:flex;align-items:center;padding:3px;gap:4px}
        .menu button{height:27px;border:0;border-radius:5px;background:transparent;color:var(--muted);font-size:clamp(11px,.8vw,14px);font-weight:800;text-transform:uppercase;padding:0 12px;cursor:pointer}
        .menu button.active{background:var(--brand);color:#fff;box-shadow:0 8px 18px rgba(23,107,135,.22)}
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
        .capacity{padding:11px 13px 10px}.capacity .chart-area{gap:clamp(18px,1.8vw,36px);padding-top:28px;background:repeating-linear-gradient(to top,var(--surface-soft) 0,var(--surface-soft) 15px,var(--grid) 16px)}
        .capacity .chart-area{position:relative}.capacity .group{position:relative;z-index:2}.capacity .bars{position:relative}.capacity .vbar{width:clamp(34px,2.45vw,48px);background:linear-gradient(180deg,var(--brand),#0f5268)}.capacity .vbar.alt{background:linear-gradient(180deg,#9fc06a,#6d8737)}
        .capacity .vbar small{background:#17202d;color:#fff}.capacity .vbar.alt small{background:#43591f;color:#fff}.capacity .input-line-chart{position:absolute;left:24px;right:24px;top:28px;bottom:clamp(20px,1.24vw,25px);z-index:4;overflow:visible;pointer-events:none}.capacity .input-line-chart polyline{fill:none;stroke:#f59e0b;stroke-width:2.6;stroke-linecap:round;stroke-linejoin:round;filter:drop-shadow(0 1px 0 rgba(255,255,255,.9))}.capacity .input-line-chart circle{fill:#fff7ed;stroke:#f59e0b;stroke-width:2.4;pointer-events:auto}
        .kpi{background:linear-gradient(180deg,#fff,#f6fafc);border:1px solid rgba(219,228,238,.95);border-radius:7px;padding:12px;color:var(--ink);box-shadow:var(--shadow)}
        .kpi span{display:block;font-size:clamp(12px,.82vw,15px);font-weight:850;text-transform:uppercase;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.kpi strong{display:block;margin-top:7px;font-size:clamp(26px,1.92vw,36px);line-height:1;font-weight:900;color:var(--ink)}.kpi small{font-size:clamp(14px,1vw,19px);font-weight:850;color:var(--muted)}
        .kpi.balance-detail{display:grid;grid-template-rows:auto minmax(0,1fr);gap:5px}.kpi-balance-list{min-height:0;display:grid;gap:2px;align-content:start}.kpi-balance-row{display:grid;grid-template-columns:minmax(70px,1fr) 8px minmax(54px,.8fr) 26px;gap:5px;align-items:center;color:var(--ink);font-size:clamp(12px,.84vw,16px);font-weight:750;line-height:1.08}.kpi-balance-row b{text-align:right;font-size:inherit}.kpi-balance-row small{color:var(--muted);font-size:clamp(10px,.7vw,13px);font-weight:750}
        .condition-card{padding:11px 16px;display:grid;grid-template-columns:182px 132px minmax(0,1fr) 110px;align-items:center;gap:14px;border-left:6px solid var(--brand)}.condition-card h2{margin:0;color:var(--muted);font-size:clamp(11px,.76vw,13px);font-weight:850;text-transform:uppercase;letter-spacing:.04em}.condition-level{display:inline-flex;align-items:center;justify-content:center;height:38px;border-radius:999px;font-size:clamp(17px,1.18vw,23px);font-weight:950;text-transform:uppercase}.condition-text{min-width:0;color:var(--ink);font-size:clamp(13px,.92vw,17px);font-weight:800;line-height:1.25}.condition-meta{justify-self:end;color:var(--muted);font-size:clamp(10px,.7vw,13px);font-weight:850;text-transform:uppercase;text-align:right}.condition-shortage{grid-column:4;justify-self:end;color:var(--muted);font-size:clamp(10px,.72vw,13px);font-weight:850;text-transform:uppercase;white-space:nowrap;text-align:right}.condition-shortage b{color:var(--ink);font-size:clamp(13px,.92vw,18px);font-weight:950}.condition-card.good{border-color:#bbf7d0;border-left-color:var(--ok);background:linear-gradient(90deg,#f4fff7 0,#fff 62%)}.condition-card.good .condition-level{background:#dcfce7;color:#166534}.condition-card.watch{border-color:#fde68a;border-left-color:var(--warn);background:linear-gradient(90deg,#fffbea 0,#fff 62%)}.condition-card.watch .condition-level{background:#fef3c7;color:#92400e}.condition-card.risk{border-color:#fecaca;border-left-color:var(--risk);background:linear-gradient(90deg,#fff5f5 0,#fff 62%)}.condition-card.risk .condition-level{background:#fee2e2;color:#991b1b}
        .condition-card.simple{grid-template-columns:220px 150px minmax(0,1fr) 130px}.condition-card.simple .condition-text{display:none}.condition-card.simple .condition-meta{display:none}
        .analytics{padding:11px 14px;display:grid;grid-template-rows:auto minmax(0,1fr);gap:9px}.analytics h2,.summary h2,.priority h2{margin:0;color:var(--ink);font-size:clamp(13px,.92vw,17px);font-weight:850;text-transform:uppercase;letter-spacing:.02em}
        .metric-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.metric{border:1px solid var(--line);border-radius:8px;background:#fff;padding:10px 72px 10px 12px;min-width:0;box-shadow:0 8px 20px rgba(16,32,51,.04);position:relative;overflow:visible}.metric span{display:block;color:var(--muted);font-size:clamp(9px,.64vw,12px);font-weight:850;text-transform:uppercase;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.metric strong{display:block;margin-top:6px;color:var(--ink);font-size:clamp(17px,1.36vw,26px);line-height:1;font-weight:900}.metric small{font-size:clamp(10px,.7vw,13px);color:var(--muted);font-weight:800}.metric.good{border-color:#bbf7d0;background:#f8fff9}.metric.watch{border-color:#fde68a;background:#fffdf1}.metric.risk{border-color:#fecaca;background:#fff8f8}.detail-btn{position:absolute;right:10px;bottom:9px;height:24px;border:1px solid var(--line);border-radius:6px;background:#fff;color:var(--brand);font-size:10px;font-weight:850;text-transform:uppercase;padding:0 9px;cursor:pointer}.detail-btn:hover{border-color:var(--brand);background:#eef7fa}.summary-item{padding-right:72px}.summary-item .detail-btn{height:22px;right:9px;bottom:8px;font-size:9px;padding:0 8px}
        .insights{min-height:0;overflow:hidden;display:grid;grid-template-columns:1fr;gap:7px;align-content:start}.insight{display:grid;grid-template-columns:9px 1fr;gap:9px;align-items:start;min-width:0;border:1px solid var(--grid);border-radius:8px;background:#fff;padding:8px 9px}.badge{width:9px;height:9px;border-radius:999px;margin-top:5px;background:var(--brand)}.insight.good .badge,.accuracy-row.good .badge,.action-row.good .badge{background:var(--ok)}.insight.watch .badge,.accuracy-row.watch .badge,.action-row.watch .badge{background:var(--warn)}.insight.risk .badge,.accuracy-row.risk .badge,.action-row.risk .badge{background:var(--risk)}.insight b{display:block;color:var(--ink);font-size:clamp(12px,.82vw,14px);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.insight p{margin:2px 0 0;color:var(--muted);font-size:clamp(10px,.72vw,13px);line-height:1.28}
        .summary{padding:11px 14px;display:grid;grid-template-rows:auto auto auto auto;gap:9px}.summary-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;align-content:start}.summary-item{border:1px solid var(--grid);border-radius:8px;background:#fff;padding:8px 72px 8px 10px;position:relative;overflow:visible}.summary-item span{display:block;color:var(--muted);font-size:clamp(9px,.64vw,12px);font-weight:850;text-transform:uppercase;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.summary-item strong{display:block;margin-top:4px;color:var(--ink);font-size:clamp(15px,1vw,20px);font-weight:900}.accuracy-list{min-height:0;overflow:visible;display:grid;gap:7px;align-content:start}.accuracy-row{border:1px solid var(--grid);border-radius:8px;background:#fff;padding:8px;display:grid;grid-template-columns:9px 1fr;gap:9px;position:relative;overflow:visible}.accuracy-row b{display:block;color:var(--ink);font-size:clamp(11px,.74vw,13px);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.accuracy-row p{margin:2px 0 0;color:var(--muted);font-size:clamp(10px,.68vw,12px);line-height:1.24}.section-label{margin:0;color:var(--muted);font-size:clamp(10px,.68vw,13px);font-weight:850;text-transform:uppercase}.action-card{padding:11px 14px;display:grid;grid-template-rows:auto minmax(0,1fr);gap:9px}.action-card h2{margin:0;color:var(--ink);font-size:clamp(13px,.92vw,17px);font-weight:850;text-transform:uppercase;letter-spacing:.02em}.action-list{min-height:0;overflow:visible;display:grid;gap:8px;align-content:start}.action-row{display:grid;grid-template-columns:9px 1fr;gap:9px;border:1px solid var(--grid);border-radius:8px;background:#fff;padding:8px 9px;position:relative;overflow:visible}.action-row b{display:block;color:var(--ink);font-size:clamp(11px,.76vw,14px);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.action-row p{margin:3px 0 0;color:var(--muted);font-size:clamp(10px,.68vw,12px);line-height:1.24}.action-row p strong{color:var(--ink);font-weight:850}.hover-detail{display:none}
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
    </style>
</head>
<body>
<div class="page">
    <header class="top">
        <div class="title">DASHBOARD HEAT TRANSFER</div>
        <nav class="menu" aria-label="Menu dashboard">
            <button type="button" class="active" data-view="dashboard">Dashboard</button>
            <button type="button" data-view="analytic">Analytic</button>
        </nav>
        <button type="button" class="workday-open" id="workdayOpen">Production Calender</button>
        <div class="last-update" id="lastUpdateBox">
            <button type="button" id="lastUpdate">*Last Update : -</button>
            <div class="last-update-panel" id="lastUpdatePanel">
                <h3>Data Terakhir</h3>
                <div id="lastUpdateRows"></div>
            </div>
        </div>
    </header>

    <main class="dashboard view active" id="dashboardView">
        <section class="left">
            <div class="top-charts">
                <div class="box chart-box qty">
                    <h2 class="chart-title">QTY PDK vs QTY OUTPUT</h2>
                    <div id="qtyPdkOutputChart"></div>
                    <div class="legend"><span><i class="dot"></i>QTY PDK</span><span><i class="dot alt"></i>QTY OUT</span></div>
                </div>
                <div class="box chart-box ready">
                    <h2 class="chart-title">READY TO LOAD PRODUCTION</h2>
                    <div id="readyToLoadChart"></div>
                </div>
            </div>
                <div class="box chart-box capacity">
                    <h2 class="chart-title">KAPASITAS vs OUTPUT vs INPUT</h2>
                    <div id="outputCapacityChart"></div>
                <div class="legend"><span><i class="dot"></i>KAPASITAS</span><span><i class="dot output"></i>OUTPUT</span><span><i class="line-key"></i>INPUT</span></div>
            </div>
        </section>

        <section class="right">
            <div class="kpis">
                <article class="kpi"><span>Total Output :</span><strong><b id="totalOutput">-</b> <small>Pcs</small></strong></article>
                <article class="kpi"><span>Balance Qty :</span><strong><b id="balanceQty">-</b> <small>Pcs</small></strong></article>
                <article class="kpi balance-detail"><span>Qty Yang Kurang :</span><div class="kpi-balance-list" id="balanceBreakdown"></div></article>
            </div>
            <div class="box priority">
                <h2>Top 10 Priority Orders Ready For Production :</h2>
                <div class="table-scroll">
                    <table>
                        <thead><tr><th>No.</th><th></th><th>Style</th><th>Tgl. Delivery</th><th>Qty Ready</th><th></th></tr></thead>
                        <tbody id="priorityRows"></tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>

    <main class="view analytics-view" id="analyticView">
        <section class="box condition-card" id="overallCondition">
            <h2>Kondisi Utama Area :</h2>
            <div class="condition-level" id="conditionLevel">-</div>
            <div class="condition-text" id="conditionSummary">-</div>
            <div class="condition-shortage" id="conditionShortage">Kurang: <b>-</b> Pcs</div>
            <div class="condition-meta" id="conditionMeta">Overall Level</div>
        </section>
        <section class="box analytics">
            <h2>Data-Driven & Management Analytics :</h2>
            <div class="metric-grid" id="analyticsMetrics"></div>
        </section>
        <section class="analytics-detail">
            <div class="box analytics">
                <h2>Management Insight :</h2>
                <div class="insights" id="analyticsInsights"></div>
            </div>
            <div class="box summary">
                <h2>Management Summary :</h2>
                <div class="summary-grid" id="analyticsSummary"></div>
                <div class="section-label">Data Accuracy Issues</div>
                <div class="accuracy-list" id="dataAccuracyRows"></div>
            </div>
            <div class="box action-card">
                <h2>Prevention & Handling :</h2>
                <div class="action-list" id="managementActions"></div>
            </div>
        </section>
    </main>
</div>

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
            <h3 id="workdayModalTitle">Production Calender</h3>
            <button type="button" class="modal-close" id="workdayModalClose" aria-label="Tutup kalender">&times;</button>
        </div>
        <div class="modal-body">
            <div class="calendar-tools">
                <button type="button" id="calendarPrev">Sebelumnya</button>
                <div class="calendar-month-title" id="calendarTitle">-</div>
                <button type="button" id="calendarNext">Berikutnya</button>
            </div>
            <div class="calendar-summary" id="calendarSummary"></div>
            <div class="calendar-grid" id="calendarRows"></div>
            <div class="calendar-legend">
                <span><i class="legend-sunday"></i>Minggu/off</span>
                <span><i class="legend-work"></i>Kerja</span>
                <span><i class="legend-half"></i>1/2 hari</span>
                <span><i class="legend-off"></i>Libur</span>
            </div>
            <div class="calendar-help">Klik tanggal untuk mengganti status. Minggu bisa dijadikan kerja bila diperlukan.</div>
            <div class="modal-actions">
                <span class="workday-message" id="workdayMessage"></span>
                <button type="button" id="workdayCancel">Batal</button>
                <button type="button" class="primary" id="workdaySave">Simpan</button>
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
                <label>Username<input type="text" id="calendarUsername" autocomplete="username"></label>
                <label>Password<input type="password" id="calendarPassword" autocomplete="current-password"></label>
            </div>
            <div class="modal-actions">
                <span class="login-message" id="calendarLoginMessage"></span>
                <button type="button" id="calendarLoginCancel">Batal</button>
                <button type="button" class="primary" id="calendarLoginSubmit">Login</button>
            </div>
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
    download: <?= json_encode($download_url) ?>
};
const rupiah = new Intl.NumberFormat('id-ID');
const percentNumber = new Intl.NumberFormat('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2});
const fmt = (value) => value === null || value === undefined || isNaN(Number(value)) ? '-' : rupiah.format(Number(value));
const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
const dateOnly = (value) => value ? new Intl.DateTimeFormat('id-ID', {day:'2-digit', month:'long', year:'numeric'}).format(new Date(value)) : '-';
const dateTime = (value) => value ? new Intl.DateTimeFormat('id-ID', {day:'2-digit', month:'long', year:'numeric', hour:'2-digit', minute:'2-digit'}).format(new Date(value)) : '-';
let currentDashboard = null;
let calendarCursor = new Date();
let selectedHolidays = new Set();
let selectedHalfDays = new Set();
let selectedWorkDays = new Set();
let calendarAuthenticated = false;

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
    document.querySelector('.dashboard').innerHTML = `<div class="box" style="grid-column:1/-1;padding:24px;text-align:center">${esc(data?.message || 'Data dashboard belum tersedia.')}</div>`;
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
    const max = Math.max(...rows.flatMap(row => keys.map(key => Number(row[key]) || 0)), 1);
    const maxBarHeight = Math.max(72, target.clientHeight - 72);
    const inputPoints = rows.map((row, index) => {
        const x = rows.length > 0 ? ((index + 0.5) / rows.length) * 100 : 50;
        const y = 100 - ((Number(row.input) || 0) / max * 100);
        return {x, y, value: Number(row.input) || 0, label: row.label || '-'};
    });
    const inputPath = inputPoints.map(point => `${point.x.toFixed(2)},${point.y.toFixed(2)}`).join(' ');

    target.innerHTML = `
        <div class="chart-area">
            <svg class="input-line-chart" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                <polyline points="${esc(inputPath)}"></polyline>
                ${inputPoints.map(point => `
                    <circle cx="${point.x.toFixed(2)}" cy="${point.y.toFixed(2)}" r="2.3" vector-effect="non-scaling-stroke">
                        <title>INPUT ${esc(point.label)}: ${esc(fmt(Math.round(point.value)))} pcs</title>
                    </circle>
                `).join('')}
            </svg>
            ${rows.map(row => {
                return `
                <div class="group">
                    <div class="bars">
                        ${['capacity', 'output'].map((key, index) => {
                            const value = Number(row[key]) || 0;
                            const height = Math.max(2, value / max * maxBarHeight);
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
    ` : '<div class="detail-empty">Data detail belum tersedia.</div>');
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
        const rows = (dashboard.output_vs_capacity || []).map(row => `
            <tr>
                ${tableCell(row.label)}
                ${tableCell(row.output, true)}
                ${tableCell(row.input, true)}
                ${tableCell(row.capacity, true)}
                ${tableCell((Number(row.capacity) || 0) - (Number(row.output) || 0), true)}
                ${tableCell(row.capacity_captured_at ? dateTime(row.capacity_captured_at) : '-')}
                ${tableCell((row.capacity_breakdown || []).map(item => `${item.label}: ${fmt(Math.round(item.balance || 0))}/${fmt(Number(item.days_left || 0).toFixed(1))}=${fmt(Math.round(item.daily_capacity || 0))}`).join(', ') || '-')}
            </tr>
        `);
        renderDetailTable('Detail Daily Output', ['Hari', 'Output', 'Input 32a', 'Capacity', 'Gap', 'Snapshot', 'History Kapasitas'], rows, [
            ['Balance Qty', `${fmt(Math.round(balanceQty))} Pcs`],
            ['Calendar Days', `${fmt(Number(calendar.remaining_workdays || 0).toFixed(1))} Days`],
            ['Export Days Left', `${fmt(daysLeft.toFixed(1))} Days`],
            ['Req. Daily', `${fmt(Math.round(requiredDaily))} Pcs/Day`]
        ], `Required Daily Output = SUM(balance tiap delivery / sisa hari kerja export tiap delivery). Card kalender menampilkan hari produktif sesuai tanda kalender, sedangkan Status Export memakai buffer 4 hari. Avg Daily Output saat ini: ${fmt(Math.round(avgOutput))} pcs/day. Gap per hari = Capacity - Output.`);
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

    const conditionCard = document.getElementById('overallCondition');
    conditionCard.className = `box condition-card simple ${esc(condition.status || '')}`;
    document.querySelector('#overallCondition h2').textContent = condition.title || '-';
    document.getElementById('conditionLevel').textContent = condition.level || '-';
    document.querySelector('#conditionShortage b').textContent = fmt(Math.round(details.output?.balance_qty || 0));
    document.getElementById('conditionSummary').textContent = '';
    document.getElementById('conditionMeta').textContent = '';

    document.getElementById('analyticsMetrics').innerHTML = metrics.map(item => `
        <div class="metric ${esc(item.status || '')}">
            <span>${esc(item.label)}</span>
            <strong>${metricValue(item)} <small>${esc(item.suffix)}</small></strong>
            <button type="button" class="detail-btn" onclick="openAnalyticsDetail('${esc(detailKeyForMetric(item.label))}')">Detail</button>
        </div>
    `).join('');

    document.getElementById('analyticsInsights').innerHTML = insights.map(item => `
        <div class="insight ${esc(item.status || '')}">
            <i class="badge"></i>
            <div>
                <b>${esc(item.title)}</b>
                <p>${esc(item.text)}</p>
            </div>
        </div>
    `).join('');

    const summaryCards = [
        ['Total Ready Load', `${fmt(Math.round(summary.total_ready || 0))} Pcs`, [`Ready periods: ${(details.ready?.periods || []).map(row => `${row.label} ${fmt(Math.round(row.ready || 0))} pcs`).join(', ') || '-'}`], 'ready'],
        ['Avg Daily Output', `${fmt(Math.round(summary.avg_daily_output || 0))} Pcs`, [`Required daily output: ${fmt(Math.round(details.daily_requirement?.required_daily_output || 0))} pcs/day`], 'daily'],
        ['Avg Daily Capacity', `${fmt(Math.round(summary.avg_daily_capacity || 0))} Pcs`, ['Capacity = total kebutuhan harian dari semua delivery aktif.'], 'daily'],
        [capacityGapLabel, `${fmt(Math.round(Math.abs(capacityGap)))} Pcs`, ['Selisih total kapasitas dengan output harian.'], 'daily'],
        ['Critical Orders', `${fmt(Math.round(summary.critical_orders || 0))} Order`, (details.priority?.orders || []).slice(0, 3).map(row => `${row.order} ${row.delivery}: ${fmt(Math.round(row.qty_ready || 0))} pcs`), 'critical'],
        ['Data Accuracy', `${fmt(Math.round(summary.data_accuracy_score || 0))}%`, accuracyIssues.map(item => `${item.title}: ${item.text}`), 'accuracy'],
        ['Sequence Issues', `${fmt(Math.round(summary.sequence_issues || 0))} Issue`, accuracyIssues.map(item => `${item.title}: ${item.text}`), 'accuracy']
    ];

    document.getElementById('analyticsSummary').innerHTML = summaryCards.map((item, index) => `
        <div class="summary-item">
            <span>${esc(item[0])}</span>
            <strong>${esc(item[1])}</strong>
            <button type="button" class="detail-btn" onclick="openAnalyticsDetail('${esc(item[3])}')">Detail</button>
        </div>
    `).join('');

    document.getElementById('managementActions').innerHTML = actions.map(item => `
        <div class="action-row ${esc(item.status || '')}">
            <i class="badge"></i>
            <div>
                <b>${esc(item.title)}</b>
                <p><strong>Hasil:</strong> ${esc(item.result)}</p>
                <p><strong>Pencegahan:</strong> ${esc(item.prevention)}</p>
                <p><strong>Penanganan:</strong> ${esc(item.handling)}</p>
            </div>
        </div>
    `).join('');

    document.getElementById('dataAccuracyRows').innerHTML = (accuracyIssues.length ? accuracyIssues : [{
        status: dataAccuracy.status || 'good',
        title: 'Urutan data valid',
        text: dataAccuracy.message || 'Tidak ada masalah urutan periode yang terdeteksi.'
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
                    <p>Kapasitas: ${fmt(Math.round(capacity))} pcs</p>
                    <p>Input 32a: ${fmt(Math.round(input))} pcs</p>
                    <p>Output: ${fmt(Math.round(output))} pcs</p>
                    <p>${gap >= 0 ? 'Gap' : 'Surplus'}: ${fmt(Math.round(Math.abs(gap)))} pcs</p>
                </div>
            `}).join('')}
        </div>
    ` : '<div class="db-last-empty">Data grafik KAPASITAS vs OUTPUT belum tersedia.</div>';
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
            <small>Pcs</small>
        </div>
    `).join('') : '<div class="db-last-empty">Tidak ada qty kurang.</div>';
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
    const title = new Intl.DateTimeFormat('id-ID', {month:'long', year:'numeric'}).format(monthStart);
    const today = isoDate(new Date());
    const dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    const cells = [];

    for (let i = 0; i < 42; i++) {
        const date = new Date(first);
        date.setDate(first.getDate() + i);
        const iso = isoDate(date);
        const isCurrentMonth = date.getMonth() === monthStart.getMonth();
        const isSunday = date.getDay() === 0;
        const isHoliday = selectedHolidays.has(iso);
        const isHalf = selectedHalfDays.has(iso);
        const isWork = selectedWorkDays.has(iso);
        const label = isHoliday ? 'Libur' : (isHalf ? '1/2 Hari' : (isWork ? 'Kerja' : (isSunday ? 'Minggu' : 'Kerja')));
        cells.push(`
            <button type="button" class="calendar-day ${isCurrentMonth ? '' : 'out'} ${isSunday ? 'sunday' : ''} ${isWork ? 'work' : ''} ${isHalf ? 'half' : ''} ${isHoliday ? 'holiday' : ''} ${iso === today ? 'today' : ''}" data-date="${iso}">
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
            <b>${fmt(calendarWorkdaysForLabel(item.label) ?? item.total_workdays)} Hari</b>
        </div>
    `).join('') : '';
}

function cycleCalendarDay(date) {
    const day = new Date(`${date}T00:00:00`).getDay();
    const isSunday = day === 0;
    const isHoliday = selectedHolidays.has(date);
    const isHalf = selectedHalfDays.has(date);
    const isWork = selectedWorkDays.has(date);

    selectedHolidays.delete(date);
    selectedHalfDays.delete(date);
    selectedWorkDays.delete(date);

    if (isSunday) {
        if (!isWork && !isHalf && !isHoliday) {
            selectedWorkDays.add(date);
        } else if (isWork) {
            selectedHalfDays.add(date);
        } else if (isHalf) {
            selectedHolidays.add(date);
        }
        return;
    }

    if (!isHalf && !isHoliday) {
        selectedHalfDays.add(date);
    } else if (isHalf) {
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
    message.textContent = 'Login...';

    try {
        const response = await fetch(urls.calendarLogin, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({username, password})
        });
        const result = await response.json();
        if (!response.ok || !result.ok) {
            throw new Error(result.message || 'Login gagal.');
        }
        calendarAuthenticated = true;
        closeCalendarLoginModal();
        openWorkdayModal();
    } catch (error) {
        message.textContent = error.message || 'Login gagal.';
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
    const workDays = Array.from(selectedWorkDays).sort();

    button.disabled = true;
    message.textContent = 'Menyimpan...';

    try {
        const response = await fetch(urls.saveWorkdays, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({holidays, half_days: halfDays, work_days: workDays})
        });
        const result = await response.json();
        if (!response.ok || !result.ok) {
            if (response.status === 401) {
                calendarAuthenticated = false;
            }
            throw new Error(result.message || 'Gagal menyimpan hari kerja.');
        }
        message.textContent = result.message || 'Tersimpan.';
        await loadStatus();
        await logoutCalendar();
        closeWorkdayModal();
    } catch (error) {
        message.textContent = error.message || 'Gagal menyimpan hari kerja.';
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
    const analyticsDetails = dashboard.management_analytics?.details || {};
    const balanceQty = numberValue(
        dashboard.kpis?.balance_qty,
        analyticsDetails.daily_requirement?.balance_qty,
        analyticsDetails.output?.balance_qty
    );
    const latestCapacity = (dashboard.output_vs_capacity || []).slice(-1)[0] || {};
    document.getElementById('lastUpdate').textContent = `*Last Update : ${latestCapacity.label || '-'}`;
    renderLastUpdateList(dashboard, data.server_time);
    document.getElementById('totalOutput').textContent = fmt(Math.round(numberValue(dashboard.kpis?.total_output)));
    document.getElementById('balanceQty').textContent = fmt(Math.round(balanceQty));
    renderBalanceBreakdown(dashboard.qty_pdk_vs_output);

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
            <td class="unit">Pcs</td>
        </tr>
    `).join('');
}

async function loadStatus() {
    const response = await fetch(urls.status, {cache:'no-store'});
    render(await response.json());
}

document.querySelectorAll('.menu button').forEach(button => {
    button.addEventListener('click', () => setActiveView(button.dataset.view));
});

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

document.getElementById('workdayOpen').addEventListener('click', () => {
    if (calendarAuthenticated) {
        openWorkdayModal();
    } else {
        openCalendarLoginModal();
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

document.getElementById('calendarLoginClose').addEventListener('click', () => {
    closeCalendarLoginModal();
});

document.getElementById('calendarLoginCancel').addEventListener('click', () => {
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
        closeCalendarLoginModal();
    }
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        closeDetailModal();
        closeWorkdayModalAndLogout();
        closeCalendarLoginModal();
    }
});

loadStatus();
setInterval(loadStatus, 60 * 60 * 1000);
</script>
</body>
</html>
