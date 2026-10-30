<?php
/** Lightweight PDF and XLSX writers (PHP native; no Composer dependency). */
final class CocoPdf {
 private array $pages=[];
 private array $commands=[];
 public function newPage():void {if($this->commands)$this->pages[]=$this->commands;$this->commands=[];$this->rect(0,811,595,31,[0.067,0.22,0.18]);$this->text(40,822,'CocoLink  /  Circular Supply Chain',12,true,[1,1,1]);$this->line(40,48,555,48,[0.7,0.77,0.72]);}
 private function literal(string $s):string { $s=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s)?:$s;$s=preg_replace('/[^\x20-\x7E]/','?',$s);return str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$s); }
 public function text(float $x,float $y,string $s,float $size=10,bool $bold=false,array $rgb=[0.14,0.21,0.18]):void {$this->commands[]=sprintf('%.3f %.3f %.3f rg BT /%s %.1f Tf %.1f %.1f Td (%s) Tj ET',$rgb[0],$rgb[1],$rgb[2],$bold?'F2':'F1',$size,$x,$y,$this->literal($s));}
 public function line(float $x1,float $y1,float $x2,float $y2,array $rgb=[0.85,0.89,0.86]):void {$this->commands[]=sprintf('%.3f %.3f %.3f RG %.1f %.1f m %.1f %.1f l S',$rgb[0],$rgb[1],$rgb[2],$x1,$y1,$x2,$y2);}
 public function rect(float $x,float $y,float $w,float $h,array $rgb):void {$this->commands[]=sprintf('%.3f %.3f %.3f rg %.1f %.1f %.1f %.1f re f',$rgb[0],$rgb[1],$rgb[2],$x,$y,$w,$h);}
 public function output(string $filename):never {
  if($this->commands)$this->pages[]=$this->commands;
  $kids=[];$obj=[1=>'<< /Type /Catalog /Pages 2 0 R >>',3=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',4=>'<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>'];
  foreach($this->pages as $i=>$cmds){$pageId=5+$i*2;$contentId=$pageId+1;$kids[]=$pageId.' 0 R';$cmds[]='0.4 0.5 0.44 rg BT /F1 8 Tf 42 30 Td (CocoLink  |  Dicetak '.date('d-m-Y H:i').'  |  Halaman '.($i+1).') Tj ET';$content=implode("\n",$cmds)."\n";
   $obj[$pageId]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$contentId.' 0 R >>';
   $obj[$contentId]='<< /Length '.strlen($content).' >>' . "\nstream\n".$content.'endstream';
  }
  $obj[2]='<< /Type /Pages /Kids ['.implode(' ',$kids).'] /Count '.count($this->pages).' >>';ksort($obj);
  $bin="%PDF-1.4\n";$offset=[0=>0];$max=max(array_keys($obj));
  for($i=1;$i<=$max;$i++){$offset[$i]=strlen($bin);$bin.=$i." 0 obj\n".$obj[$i]."\nendobj\n";}
  $xref=strlen($bin);$bin.="xref\n0 ".($max+1)."\n0000000000 65535 f \n";
  for($i=1;$i<=$max;$i++)$bin.=sprintf('%010d 00000 n ', $offset[$i])."\n";
  $bin.="trailer\n<< /Size ".($max+1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
  header('Content-Type: application/pdf');header('Content-Disposition: inline; filename="'.basename($filename).'"');header('Content-Length: '.strlen($bin));echo $bin;exit;
 }
}
function export_pdf_table(string $title,array $headers,array $data,string $filename):never {
 $pdf=new CocoPdf();$pageNo=0;$rowIndex=0;
 $start=function()use($pdf,$title,$headers,&$pageNo){$pdf->newPage();$pageNo++;$pdf->text(40,770,$title,17,true);$pdf->text(40,747,'Laporan operasional  |  '.date('d/m/Y'),10);$pdf->rect(40,707,515,25,[0.89,0.95,0.87]);$count=count($headers);$colWidth=515/max(1,$count);foreach($headers as $i=>$h)$pdf->text(44+$i*$colWidth,716,uni_slice($h,0,18),8,true);return 690;};
 $y=$start();$colWidth=515/max(1,count($headers));
 foreach($data as $row){if($y<75)$y=$start();$pdf->line(40,$y-7,555,$y-7);$i=0;foreach($row as $item){$max=(int)($colWidth/5.1);$str=(string)$item;if(uni_len($str)>$max)$str=uni_slice($str,0,max(2,$max-2)).'..';$pdf->text(44+$i*$colWidth,$y,$str,8);$i++;}$y-=22;$rowIndex++;}
 if(!$data)$pdf->text(44,$y,'Belum ada data.',10);
 $pdf->output($filename);
}
function zip_stored(array $files): string {
 $local='';$central='';$count=0;
 foreach($files as $name=>$content){
  $name=(string)$name;$content=(string)$content;$len=strlen($content);$nlen=strlen($name);$offset=strlen($local);$crc=crc32($content);
  $local.=pack('VvvvvvVVVvv',0x04034b50,20,0,0,0,0,$crc,$len,$len,$nlen,0).$name.$content;
  $central.=pack('VvvvvvvVVVvvvvvVV',0x02014b50,20,20,0,0,0,0,$crc,$len,$len,$nlen,0,0,0,0,0,$offset).$name;
  $count++;
 }
 return $local.$central.pack('VvvvvVVv',0x06054b50,0,0,$count,$count,strlen($central),strlen($local),0);
}
function export_xlsx(string $filename,array $headers,array $rows):never {
 // XLSX adalah paket ZIP OOXML. Dibuat langsung dengan PHP, tanpa ekstensi zip atau Composer.
 $xml='<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"/></sheetViews><sheetFormatPr defaultRowHeight="18"/><sheetData>';
 foreach(array_merge([$headers],$rows) as $ri=>$row){$xml.='<row r="'.($ri+1).'">';foreach(array_values($row) as $ci=>$cell){$col='';$n=$ci+1;while($n>0){$n--;$col=chr(65+$n%26).$col;$n=intdiv($n,26);} $coord=$col.($ri+1);
  if($ri>0&&is_numeric($cell)&&!is_string($cell))$xml.='<c r="'.$coord.'"><v>'.(float)$cell.'</v></c>';
  else $xml.='<c r="'.$coord.'" t="inlineStr"><is><t xml:space="preserve">'.htmlspecialchars((string)$cell,ENT_XML1|ENT_QUOTES,'UTF-8').'</t></is></c>';
 }$xml.='</row>';}
 $xml.='</sheetData></worksheet>';
 $files=[
 '[Content_Types].xml'=>'<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
 '_rels/.rels'=>'<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
 'xl/workbook.xml'=>'<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="CocoLink Report" sheetId="1" r:id="rId1"/></sheets></workbook>',
 'xl/_rels/workbook.xml.rels'=>'<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
 'xl/worksheets/sheet1.xml'=>$xml
 ];
 $binary=zip_stored($files);
 header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.basename($filename).'"');header('Content-Length: '.strlen($binary));echo $binary;exit;
}
