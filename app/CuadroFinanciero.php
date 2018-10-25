<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use App\Traits\SaveUserTrait;

class CuadroFinanciero extends Model
{
    use SoftDeletes, Notifiable, SaveUserTrait;
    protected $table = 'adm_cuadro_financieros';
    protected $fillable = array(
      0 => 'titulo',
      1 => 'contenido',
      2 => 'grafico',
    );
    protected $attributes = array(
      'titulo' => '',
      'contenido' => '',
      'grafico' => '',
    );
    protected $casts = array(
      'titulo' => 'string',
      'contenido' => 'string',
      'grafico' => 'string',
    );
    protected $events = array(
    );
    public function calculate($empresaId, $gestion, $html, $grafico='{}')
    {
        $ev = new \App\Evaluator($empresaId, $gestion, ['Balance General', 'Estado de Resultados y Gastos']);
        $ev2 = new \App\Evaluator($empresaId, $gestion, ['Estado de Ejecución Presupuestaria de Gastos']);
        
        $ppto = '<p class="desc-ind">La empresa cuenta con un presupuesto de Bs. {{$uf("Presup%Vig%")}}</p>
        <table style="height: 223px;" width="100%">
		<tbody>
		<tr>
		<td style="color: white; width: 236px;" rowspan="2">
		<div class="widget" style="text-align: center; background-color: #29B294; margin: 0px 12px 0px 0px; padding: 62px 0;">
		<h2>Bs. {{$uf("Presup%Vig%")}}</h2>
		<p>&nbsp;</p>
		<p>PRESUPUESTO&nbsp;</p>
		</div>
		</td>
		</tr>
		</tbody>
		</table>';
        return [$ev->calculate($html), $ev2->calculate($ppto), $ev->calculate($grafico, true)];
    }
}
