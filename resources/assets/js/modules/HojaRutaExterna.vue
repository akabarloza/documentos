<template>
    <panel v-if="data.attributes" :name="'HOJA DE RUTA - No HR - SCEP: ' + data.attributes.numero" class="panel-primary">
           <div class="container">
            <div class="row">
                <div class="col-12"><h4>Tipo HR: {{data.attributes.tipo}}</h4> </div>
            </div>
            <div class="form-group row">
                <div :class="colLabel"><label>Fecha de recepción:</label></div>
                <div :class="colField"><datetime type="date" v-model="data.attributes.fecha" /></div>
            </div>
            <div class="form-group row">
                <div :class="colLabel"><label>Procedencia:</label></div>
                <div :class="colField">
                    <select-box :data="procedencias" v-model="data.attributes.procedencia"
                        filter-by="attributes.cod_empresa,attributes.nombre_empresa">
                        <template slot-scope="{row,format}">
                            <span v-html="format(row.attributes.cod_empresa)" class="badge" style="font-size: 1rem"></span>
                            <span v-html="format(row.attributes.nombre_empresa)" style="font-size: 1rem"></span>
                        </template>
                    </select-box>
                </div>
            </div>
            <div class="form-group row">
                <div :class="colLabel"><label>Referencia:</label></div>
                <div :class="colField">
                    <text-box v-model="data.attributes.referencia" :reference="referenciarNota">
                        <template slot="dropdown" slot-scope="{code,select}">
                            <grid v-model="notas" :filter="code" :without-navbar="true"
                                  filter-by="attributes.nro_nota
                                  attributes.referencia">
                                <tr slot-scope="{row, options, format}" @click="select(row)">
                                    <td v-html="format(row.attributes.nro_nota)"></td>
                                    <td v-html="format(row.attributes.referencia)"></td>
                                </tr>
                            </grid>
                        </template>
                    </text-box>
                </div>
            </div>
            <div class="form-group row">
                <div :class="colLabel"><label>Destinatario:</label></div>
                <div :class="colField">
                    <select-box :data="destinatarios" v-model="data.attributes.destinatario"
                        filter-by="attributes.nombre_completo">
                        <template slot-scope="{row,format}">
                            <span v-html="format(row.attributes.nombre_completo)" style="font-size: 1rem"></span>
                        </template>
                    </select-box>
                </div>
            </div>
            <div class="form-group row">
                <div :class="colLabel"><label>N° de control:</label></div>
                <div :class="colField"><input class="form-control" type="text" v-model="data.attributes.nro_de_control" /></div>
            </div>
            <div class="form-group row">
                <div :class="colLabel"><label>Anexo Hojas:</label></div>
                <div :class="colField">
                    <div class="row">
                        <div class="col-lg-5 col-md-5 col-sm-6 col-xs-12 pr-0">
                            <table class="anexos">
                                <tr>
                                    <td>
                                        <input class="form-control text-center" type="number" :value="getFjs" @input="setFjs">
                                        <small class="form-text text-muted">fjs</small>
                                    </td>
                                    <td>
                                        <input class="form-control text-center" type="number" :value="getArch" @input="setArch">
                                        <small class="form-text text-muted">arch</small>
                                    </td>
                                    <td>
                                        <input class="form-control text-center" type="number" :value="getAnillados" @input="setAnillados">
                                        <small class="form-text text-muted">anillados</small>
                                    </td>
                                    <td>
                                        <input class="form-control text-center" type="number" :value="getLegajo" @input="setLegajo">
                                        <small class="form-text text-muted">legajo</small>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-lg-4 col-md-5 col-sm-6 col-xs-10 pl-sm-0">
                            <table class="anexos">
                                <tr>
                                    <td>
                                        <input class="form-control text-center" type="number" :value="getEjemplar" @input="setEjemplar">
                                        <small class="form-text text-muted">ejemplar</small>
                                    </td>
                                    <td>
                                        <input class="form-control text-center" type="number" :value="getEngrapado" @input="setEngrapado">
                                        <small class="form-text text-muted">engrapado</small>
                                    </td>
                                    <td>
                                        <input class="form-control text-center" type="number" :value="getCd" @input="setCd">
                                        <small class="form-text text-muted">cd</small>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group row">
                <div :class="colLabel"><label>Clasificación:</label></div>
                <div :class="colField">
                    <div class="radio" v-for="clasificacion in clasificacionHojasRuta">
                        <label>
                            <input type="radio" name="hoja_edit_tipo"
                                   :value="clasificacion.attributes.sigla"
                                   v-model="data.attributes.tipo_tarea">
                            {{clasificacion.attributes.nombre}}
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-group row">
                <div :class="colLabel"></div>
                <div :class="colField">
                    <button type="button" class="btn btn-primary" @click="saveHR">Guardar</button>
                </div>
            </div>
        </div>
        <div class="container" v-if="derivacion.attributes && data.id">
            <div class="row">
                <div class="col-12"><h4>Derivaciones</h4> </div>
            </div>
            <div class="form-group row">
                <div :class="colLabel"><label>Fecha de derivación:</label></div>
                <div :class="colField"><datetime type="date" v-model="derivacion.attributes.fecha" /></div>
            </div>
            <div class="form-group row">
                <div :class="colLabel"><label>Comentarios</label></div>
                <div :class="colField">
                    <text-box v-model="derivacion.attributes.comentarios" :reference="referenciarNota">
                        <template slot="dropdown" slot-scope="{code,select}">
                            <grid v-model="notas" :filter="code" :without-navbar="true"
                                  filter-by="attributes.nro_nota
                                  attributes.referencia">
                                <tr slot-scope="{row, options, format}" @click="select(row)">
                                    <td v-html="format(row.attributes.nro_nota)"></td>
                                    <td v-html="format(row.attributes.referencia)"></td>
                                </tr>
                            </grid>
                        </template>
                    </text-box>
                </div>
            </div>
            <div class="form-group row">
                <div :class="colLabel"><label>Destinatario:</label></div>
                <div :class="colField">
                    <select-box :data="destinatarios" v-model="derivacion.attributes.destinatario"
                        filter-by="attributes.nombre_completo">
                        <template slot-scope="{row,format}">
                            <span v-html="format(row.attributes.nombre_completo)" style="font-size: 1rem"></span>
                        </template>
                    </select-box>
                </div>
            </div>
            <div class="form-group row">
                <div :class="colLabel"><label>Instrucción</label></div>
                <div :class="colField">
                    <select-box :data="instrucciones" v-model="derivacion.attributes.instruccion" id-field="attributes.sigla"
                        filter-by="attributes.sigla,attributes.nombre">
                        <template slot-scope="{row,format}">
                            <span v-html="format(row.attributes.nombre)" style="font-size: 1rem"></span>
                        </template>
                    </select-box>
                </div>
            </div>
            <div class="form-group row">
                <div :class="colLabel"><label>Dias plazo:</label></div>
                <div :class="colField"><input class="form-control" type="number" v-model="derivacion.attributes.dias_plazo" /></div>
            </div>
        </div>
    </panel>
</template>

<script>
    export default {
        path: "/HojaRutaExterna/:id",
        computed: {
            getFjs() {
                return this.getAnexo("fjs");
            },
            getArch() {
                return this.getAnexo("arch");
            },
            getAnillados() {
                return this.getAnexo("anillados");
            },
            getLegajo() {
                return this.getAnexo("legajo");
            },
            getEjemplar() {
                return this.getAnexo("ejemplar");
            },
            getEngrapado() {
                return this.getAnexo("engrapado");
            },
            getCd() {
                return this.getAnexo("cd");
            },
        },
        methods: {
            referenciarNota(nota) {
                return nota.attributes.nro_nota + " " + nota.attributes.referencia;
            },
            saveHR() {
                this.data.postToAPI("/api/hoja_rutas").then((response) => {
                    console.log(response.data);
                    this.$router.push({path: this.apiBase, params: {id: response.data.data.id}});
                });
            },
            getIdURL() {
                return isNaN(this.$route.params.id) ? 'create?factory=' + this.$route.params.id : this.$route.params.id;
            },
            setFjs(event) {
                this.setAnexo('fjs', event.target.value);
            },
            setArch(event) {
                this.setAnexo('arch', event.target.value);
            },
            setAnillados(event) {
                this.setAnexo('anillados', event.target.value);
            },
            setLegajo(event) {
                this.setAnexo('legajo', event.target.value);
            },
            setEjemplar(event) {
                this.setAnexo('ejemplar', event.target.value);
            },
            setEngrapado(event) {
                this.setAnexo('engrapado', event.target.value);
            },
            setCd(event) {
                this.setAnexo('cd', event.target.value);
            },
            getAnexo(index) {
                let regexp = new RegExp("(\\d+)\\s+" + index);
                let ma = this.data.attributes.anexo_hojas ? this.data.attributes.anexo_hojas.match(regexp) : null;
                return ma ? ma[1] : "";
            },
            setAnexo(anexoA, value) {
                const anexoHojas = [];
                const tipos = [
                    'fjs',
                    'arch',
                    'anillados',
                    'legajo',
                    'ejemplar',
                    'engrapad',
                    'cd',
                ];
                for (let index of tipos) {
                    let anexo = index == anexoA ? value : this.getAnexo(index);
                    if (anexo) {
                        anexoHojas.push(anexo + ' ' + index);
                    }
                }
                this.data.attributes.anexo_hojas = anexoHojas.join(', ');
            }
        },
        data() {
            return {
                data: new ApiObject('/api/hoja_rutas/' + this.getIdURL()),
                procedencias: new ApiArray('/api/empresas'),
                destinatarios: new ApiArray('/api/users'),
                notas: new ApiArray('/api/notas_oficio?sort=-id&per_page=5000'),
                clasificacionHojasRuta: new ApiArray('/api/hoja_ruta_clasificacion'),
                derivacion: new ApiObject('/api/derivacion/create'),
                instrucciones: new ApiArray('/api/instruccion'),
            };
        },
        watch: {
            '$route.params.id'() {
                this.data.loadFromAPI('/api/hoja_rutas/' + this.getIdURL());
            }
        }
    };
</script>

<style lang="scss">
    .anexos tr td {
    }
    .anexos tr td small {
    }
</style>
