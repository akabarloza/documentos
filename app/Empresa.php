<?php

namespace App;

use App\Traits\SaveUserTrait;
use App\Traits\AutoTableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Empresa extends Model
{

    use SoftDeletes,
        Notifiable,
        SaveUserTrait;
    use AutoTableTrait;

    protected $table = 'adm_empresas';
    protected $fillable = [
        'cod_empresa',
        'nombre_empresa',
        'corporacion',
        'caracter',
        'rubro',
        'tipologia',
        'detalle_empresa',
        'sub_empresa',
        'es_principal',
    ];
    protected $attributes = array(
        'cod_empresa' => '',
        'nombre_empresa' => '',
        'corporacion' => '',
        'caracter' => '',
        'rubro' => '',
        'tipologia' => '',
        'detalle_empresa' => '',
        'sub_empresa' => '0',
        'es_principal' => '0',
    );
    protected $casts = array(
        'cod_empresa' => 'string',
        'nombre_empresa' => 'string',
        'corporacion' => 'string',
        'caracter' => 'string',
        'rubro' => 'string',
        'tipologia' => 'string',
        'detalle_empresa' => 'string',
        'sub_empresa' => 'string',
        'es_principal' => 'string',
    );
    protected $events = array(
    );

    public function estados()
    {
        return $this->hasMany('App\EstadoFinanciero');
    }

    public function graficos()
    {
        return $this->hasMany('App\EmpresaGrafico');
    }

    public function getDetalleEmpresaAttribute($value)
    {
        $value = str_replace('{{$uc(\'3\')}}',
            '<span class="calculado" title="patrimonio">{{$uc(\'3\')}}</span>',
            $value);
        $ev = new \App\Evaluator($this->id, '2017',
            ['Balance General', 'Estado de Resultados y Gastos']);
        return $ev->calculate($value);
    }

    public function setDetalleEmpresaAttribute($value)
    {
        $dom = new \DOMDocument;
        $dom->loadHTML($value);
        $htmlWithTag = $value;
        foreach ($dom->getElementsByTagName('span') as $span) {
            if ($span->getAttribute('class') === 'calculado' &&
                $span->getAttribute('title') === 'patrimonio'
            ) {
                //$htmlWithTag = str_replace($dom->saveXML($span), '<span class="calculado" title="patrimonio">{{$uc(\'3\')}}</span>' , $htmlWithTag);
                $htmlWithTag = str_replace($dom->saveXML($span),
                    '{{$uc(\'3\')}}', $htmlWithTag);
            }
        }

        $this->attributes['detalle_empresa'] = $htmlWithTag;
    }

    public function eeff($gestion, $eeff)
    {
        $ev = new \App\Evaluator($this->id, $gestion);
        $res = [];
        foreach ($eeff as $key => $val) {
            $isChart = substr($key, 0, 5) === 'chart';
            $cal = @$ev->calculate($val, $isChart);
            if ($isChart) {
                $res[$key] = json_decode($cal);
            } else {
                $res[$key] = $cal;
            }
        }
        return $res;
    }

    public function procedencias()
    {
        $sql = "select nombre_empresa as nombre from adm_empresas
                                union
                                select nombre_empresa as nombre from adm_firmas";
        $res = \DB::select($sql);
        return ["data" => $res];
    }
}
