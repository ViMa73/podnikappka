<?php
namespace Services;

use Core\DB;

class AttendanceExport
{
    private static function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
    private static function tm($v): string { return empty($v) ? '' : substr((string)$v, 0, 5); }
    private static function hm(int $m): string { return sprintf('%02d:%02d', intdiv(max(0,$m),60), max(0,$m)%60); }

    public static function output(int $companyId, int $userId, string $month): void
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) { http_response_code(422); echo 'Neplatný měsíc.'; return; }
        $start=\DateTimeImmutable::createFromFormat('!Y-m-d',$month.'-01');
        if (!$start || $start->format('Y-m')!==$month) { http_response_code(422); echo 'Neplatný měsíc.'; return; }
        $end=$start->modify('last day of this month');
        $db=DB::get();
        $s=$db->prepare("SELECT id, first_name, last_name, workload_hours FROM users WHERE id=? AND company_id=? LIMIT 1");
        $s->execute([$userId,$companyId]); $user=$s->fetch();
        if (!$user) { http_response_code(404); echo 'Uživatel nebyl nalezen.'; return; }
        $s=$db->prepare("SELECT * FROM attendance_records WHERE company_id=? AND user_id=? AND work_date BETWEEN ? AND ? ORDER BY work_date");
        $s->execute([$companyId,$userId,$start->format('Y-m-d'),$end->format('Y-m-d')]);
        $by=[]; foreach(($s->fetchAll()?:[]) as $r) $by[$r['work_date']]=$r;
        $days=['Pondělí','Úterý','Středa','Čtvrtek','Pátek','Sobota','Neděle'];
        $months=[1=>'Leden','Únor','Březen','Duben','Květen','Červen','Červenec','Srpen','Září','Říjen','Listopad','Prosinec'];
        $workload=(int)round((float)($user['workload_hours']??0)*60);
        $sum=['work'=>['d'=>0,'m'=>0],'D'=>['d'=>0,'m'=>0],'N'=>['d'=>0,'m'=>0],'O'=>['d'=>0,'m'=>0],'S'=>['d'=>0,'m'=>0]];
        $prescribedDays=0; for($d=$start;$d<=$end;$d=$d->modify('+1 day')) if((int)$d->format('N')<=5) $prescribedDays++;
        $rows='';
        for($d=$start;$d<=$end;$d=$d->modify('+1 day')) {
            $key=$d->format('Y-m-d'); $r=$by[$key]??[]; $sp=(string)($r['special_code']??'');
            $code=$sp==='PN'?'N':$sp; $worked=(int)($r['worked_minutes']??0);
            if($sp==='') { if($worked>0){$sum['work']['d']++;$sum['work']['m']+=$worked;} }
            elseif(isset($sum[$code])) { $m=$worked>0?$worked:$workload; $sum[$code]['d']++;$sum[$code]['m']+=$m; $worked=$m; }
            $note=$code!==''?$code:(string)($r['note']??'');
            $rows.='<tr><td class="date">'.self::h($days[(int)$d->format('N')-1].' '.$d->format('d. m. Y')).'</td>'.
              '<td>'.self::h(self::tm($r['arrival_time']??'')).'</td><td>'.self::h(self::tm($r['departure_time']??'')).'</td>'.
              '<td>'.self::h(self::tm($r['lunch_from']??'')).'</td><td>'.self::h(self::tm($r['lunch_to']??'')).'</td>'.
              '<td>'.self::h(self::tm($r['break_from']??'')).'</td><td>'.self::h(self::tm($r['break_to']??'')).'</td>'.
              '<td>'.($worked>0?self::h(self::hm($worked)):'').'</td><td>'.self::h($note).'</td></tr>';
        }
        $totalD=$sum['work']['d']+$sum['D']['d']+$sum['N']['d']+$sum['O']['d']+$sum['S']['d'];
        $totalM=$sum['work']['m']+$sum['D']['m']+$sum['N']['m']+$sum['O']['m']+$sum['S']['m'];
        $summary=function($label,$x){return '<tr><td>'.$label.'</td><td>'.$x['d'].'</td><td>'.self::hm($x['m']).'</td></tr>';};
        $name=trim(($user['first_name']??'').' '.($user['last_name']??''));
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="cs"><head><meta charset="utf-8"><title>Evidence docházky – '.self::h($name).'</title><style>
@page{size:A4 portrait;margin:10mm}*{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#000;margin:0;font-size:10px}h1{text-align:center;font-family:Georgia,serif;font-size:24px;margin:0 0 18px}.meta{display:grid;grid-template-columns:2fr 1fr 1fr;gap:18px;font-family:Georgia,serif;font-size:15px;margin:0 12% 14px}.attendance{width:100%;border-collapse:collapse;table-layout:fixed}.attendance th,.attendance td{border:1px solid #000;padding:2px 3px;text-align:center;height:17px}.attendance th{font-weight:700}.attendance .date{width:23%;text-align:center}.attendance .note{width:13%}.attendance .worked{width:14%}.bottom{display:grid;grid-template-columns:180px 1fr;gap:45px;margin-top:38px;align-items:start}.summary{border-collapse:collapse;width:180px}.summary th,.summary td{border:1px solid #000;padding:2px 4px;text-align:center}.summary td:first-child{text-align:left}.legend{display:grid;grid-template-columns:22px 1fr;gap:5px 8px;max-width:390px}.legend h3{grid-column:1/-1;margin:0 0 6px}.sign{margin-top:28px;line-height:3.7}.no-print{position:fixed;right:16px;top:16px;background:#111;color:#fff;border:0;border-radius:8px;padding:10px 15px;cursor:pointer}@media print{.no-print{display:none}}
</style></head><body><button class="no-print" onclick="window.print()">🖨 Tisk / Uložit PDF</button><h1>EVIDENCE DOCHÁZKY</h1><div class="meta"><div>Jméno a příjmení: <b>'.self::h($name).'</b></div><div>Měsíc: <b>'.self::h($months[(int)$start->format('n')]).'</b></div><div>Rok: <b>'.self::h($start->format('Y')).'</b></div></div>
<table class="attendance"><thead><tr><th rowspan="2" class="date">Datum</th><th colspan="2">Práce</th><th colspan="2">Oběd *)</th><th colspan="2">Přestávka</th><th rowspan="2" class="worked">Odpracováno</th><th rowspan="2" class="note">Poznámka</th></tr><tr><th>Příchod</th><th>Odchod</th><th>Od</th><th>Do</th><th>Od</th><th>Do</th></tr></thead><tbody>'.$rows.'</tbody></table>
<div class="bottom"><table class="summary"><thead><tr><th>Měsíč. vyhod.</th><th>Dny</th><th>Hodiny</th></tr></thead><tbody>'.$summary('Odprac.',$sum['work']).$summary('D',$sum['D']).$summary('N',$sum['N']).$summary('O',$sum['O']).$summary('S',$sum['S']).'<tr><th>Celkem</th><th>'.$totalD.'</th><th>'.self::hm($totalM).'</th></tr><tr><td><i>Předpis</i></td><td><i>'.$prescribedDays.'</i></td><td><i>'.self::hm($prescribedDays*$workload).'</i></td></tr></tbody></table>
<div><div class="legend"><h3>Vysvětlivky</h3><b>Odprac.</b><span>Počet odpracovaných dnů a hodin</span><b>D</b><span>Dovolená</span><b>N</b><span>Nemoc / pracovní neschopnost</span><b>O</b><span>Ošetření člena rodiny</span><b>S</b><span>Svátky</span><b>*)</b><span>Přestávka na jídlo a oddech</span></div><div class="sign">Podpis pracovníka: .......................................<br>Kontrola vedoucího: .......................................</div></div></div></body></html>';
    }
}
