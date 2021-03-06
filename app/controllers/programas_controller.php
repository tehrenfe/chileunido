<?php
	class ProgramasController extends AppController
	{
		# /** 
		#  * @var Programa 
		#  */
		
		
		var $name = "Programa";
		var $uses = array("Actividad","Voluntario","Programa","Caso","Seguimiento","Beneficiario","Persona","Comuna","Respuestaficha","Dimension","Convenio");
		var $helpers = array('Html', 'Form', 'Time', 'Excel', 'Xls'); 
		
		function index($exito=null)
		{
			
			$this->escribirHeader("Administracion de Programas");
			$programas=$this->Programa->findAll();
			$this->set('programas', $programas);
			$years = array();
			for($year = date('Y'); $year>=1999; $year--){
				$years += array($year => $year);
			}
			$this->set('years',$years);
			$opciones = array();
			$opciones += array(1 => mb_convert_encoding('Dï¿½as','UTF-8','ASCII'),7 => "Semanas",30 =>"Meses");
			
			$meses = array(1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre');
			$this->set('meses',$meses);
			$this->set('opciones', $opciones);
			$this->set('msg_for_layout',$exito);
		
		}
		

		function excel(){
			set_time_limit(0);
			$mes = "Month(Caso.fec_ingreso) = ".$this->data["Opcion"]["mes"];
			
			if($this->data["Programa"]["cod_programa"]==1 || $this->data["Programa"]["cod_programa"]==2){
				
				//Se busca el codigo de actividad de las fichas iniciales, para las consultas que lo requieran.
				$actividad_inicial = $this->Actividad->find("Actividad.cod_programa = ".$this->data["Programa"]["cod_programa"]." AND Actividad.tip_actividad LIKE 'Inicial'", array('Actividad.cod_actividad'), "", -1);
				$cod_actividad_inicial = $actividad_inicial['Actividad']['cod_actividad'];
				unset($actividad_inicial);
				
				//Se busca el codigo de actividad de las fichas de cierre, para las consultas que lo requieran.
				if($this->data["Programa"]["cod_programa"]==1) $actividad_cierre = $this->Actividad->find("Actividad.cod_programa = 1 AND Actividad.tip_actividad LIKE 'Termino Embarazo'",	array('Actividad.cod_actividad'), "", -1);
				if($this->data["Programa"]["cod_programa"]==2) $actividad_cierre = $this->Actividad->find("Actividad.cod_programa = 2 AND Actividad.tip_actividad LIKE 'Cierre'", 			array('Actividad.cod_actividad'), "", -1);
				$cod_actividad_cierre = $actividad_cierre['Actividad']['cod_actividad'];
				unset($actividad_cierre);
			
				//Se busca el codigo de actividad de las fichas iniciales, para las consultas que lo requieran.
				$actividad_reactivar = $this->Actividad->find("Actividad.cod_programa = ".$this->data["Programa"]["cod_programa"]." AND Actividad.tip_actividad LIKE 'Reactivacion'", array('Actividad.cod_actividad'), "", -1);
				$cod_actividad_reactivar = $actividad_reactivar['Actividad']['cod_actividad'];
				unset($actividad_reactivar);
				
				//Se busca el codigo de actividad de las fichas clinicas, para las consultas que lo requieran.
				$actividad_cierre_clinico=$this->Actividad->find("Actividad.cod_programa = ".$this->data["Programa"]["cod_programa"]." AND Actividad.tip_actividad LIKE 'Cierre Clinico'", array('Actividad.cod_actividad'), "", -1);
				$cod_actividad_clinico  = $actividad_cierre_clinico['Actividad']['cod_actividad'];
				unset($actividad_cierre_clinico);			
			}
			
			$hoja = array();
							
			//[Dawes] Se hace la busqueda. Retorna array, cada item es un array con el caso, su voluntario, su tipocaso, sus seguimientos (como array), su beneficiario y tipo ingreso (todas las tablas con relacion directa)
			$casos=$this->Caso->query(
									  " SELECT Caso.cod_beneficiario, Caso.cod_caso, Caso.fec_ingreso, Caso.fec_retiro, Tipoingreso.nom_tipoingreso, Tipoingreso.nom_medio, Tipocaso.nom_tipocaso, Caso.est_caso, Caso.est_porquien, Caso.cod_voluntario, Caso.cod_soloyo  
									    FROM `casos` AS `Caso`, `tipocasos` AS `Tipocaso`, tipoingresos AS `Tipoingreso` 
									    WHERE (`Caso`.`cod_tipocaso` = `Tipocaso`.`cod_tipocaso`) 
											AND `Tipoingreso`.`cod_tipoingreso` = `Caso`.`cod_tipoingreso` 
											AND `Tipocaso`.`cod_programa` = ".$this->data["Programa"]["cod_programa"]."
											AND Year(Caso.fec_ingreso) = ".$this->data["Opcion"]["periodo"]." AND ".$mes
									   );
			
			foreach($casos as $caso){
				
			//-------Datos basicos del beneficiario (sacados directamente desde caso)
						
				$beneficiario = $this->Beneficiario->find(array("Persona.cod_persona"=>$caso["Caso"]["cod_beneficiario"]));
				$comuna =$this->Comuna->find(array("cod_comuna"=>$beneficiario["Persona"]["cod_comuna"]),null,null,-1);
				$convenio =$this->Convenio->find(array("cod_convenio"=>$beneficiario["Convenio"]["cod_convenio"]),null,null,-1);
				//cachar convenio si es pa acoge!!
				
				//fec ingreso beneficiario
				$fec1=explode(" ", $beneficiario['Beneficiario']['fec_ingreso']);
				$fec2=explode("-", $fec1[0]);
				$datos['fecha_inicial_bene']=$fec2[2]."-".$fec2[1]."-".$fec2[0];
				unset($fec1);
				unset($fec2);
				
				//fec ingreso caso
				$fec1=explode(" ", $caso['Caso']['fec_ingreso']);
				$fec2=explode("-", $fec1[0]);
				$datos['fecha_inicial_caso']=$fec2[2]."-".$fec2[1]."-".$fec2[0];
				unset($fec1);
				unset($fec2);
				
				$datos['tipo_ingreso']=$caso['Tipoingreso']['nom_tipoingreso'];
				$datos['medio_ingreso']=$caso['Tipoingreso']['nom_medio'];
				
				if( isset($convenio['Convenio']['nom_convenio']) )
					$datos['convenio']=$convenio['Convenio']['nom_convenio'];
				
				$datos['nombre']=$beneficiario['Persona']['nom_nombre']." ".$beneficiario['Persona']['nom_appat'];
				$datos['rol_familiar']=$beneficiario['Beneficiario']['tip_rolfamilia']; //falta codificacion fundacion (no compatible con base de datos)
				
				if(	isset($beneficiario['Persona']['ano_nacimiento']) ) {
					if($beneficiario['Persona']['ano_nacimiento']>1900)
						$datos['edad'] = date('Y')-$beneficiario['Persona']['ano_nacimiento'];
					else 
						$datos['edad'] = "Sin informaciÃ³n";
				} else
					$datos['edad'] = "Sin informaciÃ³n";
				
				$datos['telefono1']= $beneficiario['Persona']['num_telefono1'];
				$datos['telefono2']= $beneficiario['Persona']['num_telefono2'];
				
				$datos['nom_comuna']= $comuna['Comuna']['nom_comuna'];
				
				if($comuna['Comuna']['nom_gse']!=null)
					$datos['nse_comuna'] = $comuna['Comuna']['nom_gse'];
				else
					$datos['nse_comuna'] = $comuna['Comuna']['nom_zona'];
				
				$datos['motivo'] = $caso['Tipocaso']['nom_tipocaso'];
				$datos['por_quien_llama'] = $caso['Caso']['est_porquien'];
				
				$voluntario = $this->Persona->query("SELECT nom_nombre, nom_appat FROM personas AS Persona WHERE Persona.cod_persona = ".$caso["Caso"]["cod_voluntario"]);
				$datos['voluntario_ingreso'] = $voluntario[0]['Persona']['nom_nombre']." ".$voluntario[0]['Persona']['nom_appat'];
				unset($voluntario);
				
				if($caso['Caso']['cod_soloyo']!=null){
					$voluntario_soloyo = $this->Persona->query("SELECT nom_nombre, nom_appat FROM personas AS Persona WHERE Persona.cod_persona =".$caso['Caso']['cod_soloyo']); 

					$datos['solo_yo'] = $voluntario_soloyo[0]['Persona']['nom_nombre']." ".$voluntario_soloyo[0]['Persona']['nom_appat'];
					unset($voluntario_soloyo);
				} else {
					$datos['solo_yo'] = "No";
				}

				$datos['estado']=$caso['Caso']['est_caso'];

				unset($beneficiario);
				unset($convenio);
				unset($comuna);
				
				
				
				//calculo de meses activos
				if ($this->data["Programa"]["cod_programa"]==1 || $this->data["Programa"]["cod_programa"]==2)  {
					$seguimientos_inicial = $this->Seguimiento->query("SELECT fec_ejecucion FROM seguimientos AS Seguimiento WHERE cod_caso = ".$caso['Caso']['cod_caso']." AND ( cod_actividad = ".$cod_actividad_inicial." OR cod_actividad = ".$cod_actividad_reactivar." ) ORDER BY num_evento DESC");
					$seguimientos_final   = $this->Seguimiento->query("SELECT fec_ejecucion FROM seguimientos AS Seguimiento WHERE cod_caso = ".$caso['Caso']['cod_caso']." AND cod_actividad = ".$cod_actividad_cierre." ORDER BY num_evento DESC");

					//desde aqui se toman todas las fechas, se ordenan, y se trata de sumar todos los periodos entre un primer seguimiento de apertura (entre varios consecutivos si hay) y el ultimo seguimiento de cierre entre el proximo bloque de ellos consecutivo (puede ser uno solo).

					$fechas = array();
					foreach($seguimientos_inicial as $si){
						$f = null;
						$f = explode(" ", $si['Seguimiento']['fec_ejecucion']);
						$f = explode("-", $f[0]); //$fecha[2]=dia, $fecha[1]=mes, $fecha[0]=aÃ±o
						$f = mktime(0,0,0,$f[1],$f[2],$f[0]); // hora, min, seg, mes, dia, aÃ±o
						$fechas = array_merge($fechas, array($f."_apertura"));
					}	
					foreach($seguimientos_final as $sf){
						$f = null;
						$f = explode(" ", $sf['Seguimiento']['fec_ejecucion']);
						$f = explode("-", $f[0]); //$fecha[2]=dia, $fecha[1]=mes, $fecha[0]=aÃ±o
						$f = mktime(0,0,0,$f[1],$f[2],$f[0]); // hora, min, seg, mes, dia, aÃ±o
						$fechas = array_merge($fechas, array($f."_cierre"));
					}			
					sort($fechas);
					foreach($fechas as $k => $fec){
						$fechas[$k] = explode("_", $fec);
					}
					
					//para la primera fecha de inicio se toma la de ingreso del caso, esto pq hay veces que cambian la fecha manualmente.
					$fi = explode(" ", $caso['Caso']['fec_ingreso']);
					$fi = explode("-", $fi[0]); //$fecha[2]=dia, $fecha[1]=mes, $fecha[0]=aÃ±o
					$fi = mktime(0,0,0,$fi[1],$fi[2],$fi[0]); // hora, nim, seg, mes, dia, aÃ±o				
					$ff = false; 
					
					$i = 0;
					$n = count($fechas);
					$meses  = null;
					$fecha_final = null;
					while($i<$n){
						//busca la primera fecha de apertura
						while ($i<$n && !$fi) {
							if($fechas[$i][1]=="apertura") {
								$fi = $fechas[$i][0];
								$i++; //queda listo para buscar en proxima etapa
							}
							else $i++;
						}
						if($fi){
							//se busca la proxima fecha de ceirre, luego se sigue avanzando hasta encontrar la ultima fecha de cierre consecutiva.
							while($i<$n && !$ff){
								if($fechas[$i][1]=="cierre") {
									$ff = $fechas[$i][0];
									$i++;
									//ahora buscamos el ultimo cierre consecutivo, si es que hay.
									$ultimo = false;
									while($i<$n && !$ultimo){
										if($fechas[$i][1]=="cierre"){
											$ff = $fechas[$i][0];
											$i++;
										} else $ultimo = true;
									}
								}
								else $i++;
							}
							if($ff) {
								$meses += $ff - $fi;
								$fecha_final = $ff;  //para llevar registro de la ultima fecha de cierre. 	
							}
							else {
								$meses += time() - $fi;
								$fecha_final = null;  //para llevar registro de la ultima fecha de cierre.	
							} 
						}
						$fi = null;
						$ff = null;
					}
					
					if($meses === null)	$datos['meses_activo'] = null; 									//equivalencia estricta === pq sino toma 0 como null.
					else				$datos['meses_activo'] =  " ".floor($meses/(60*60*24*30.44));	//el resultado esta en segundos, por lo que se divide en seg por min, min por hora, horas por dia, dias por mes promedio. Se agrega un espacio para diferenciar de null si sale 0.
					
					if($fecha_final) $datos['fecha_cierre'] = date('d', $fecha_final)."-".date('m', $fecha_final)."-".date('Y', $fecha_final);

					unset($i, $n, $ultimo, $f, $fi, $ff, $fechas, $fecha_final, $meses);
					unset($seguimientos_inicial, $seguimientos_final);
					
				} //end if para meses
				
				
				switch ($this->data["Programa"]["cod_programa"]) {
					
		//----Programa Acoge
					case '1': {
						//limitar a solo el num evento
						//ejemplo $seguimientofinal  = $this->Seguimiento->query("SELECT num_evento FROM seguimientos AS Seguimiento WHERE cod_caso = ".$caso['Caso']['cod_caso']." AND cod_actividad = ".$cod_actividad_cierre." ORDER BY num_evento DESC");
						$seguimientoinicial = $this->Seguimiento->find(array('Seguimiento.cod_caso' => $caso['Caso']['cod_caso'], 'Seguimiento.cod_actividad' => $cod_actividad_inicial),null,'num_evento ASC' ,-1);
						if($seguimientoinicial) // hay casos que podrÃ­an estar fallados por razon X.
						{							
							//--------Datos de ficha inicial (seguimiento inicial)
							
							$respuestas = $this->Respuestaficha->query("SELECT `Respuestaficha`.`cod_respuesta`, `Respuestaficha`.`cod_subpregunta`, `Respuestaficha`.`nom_respuesta`, `Subpregunta`.`cod_dimension`, `Subpregunta`.`nom_subpregunta`  FROM `respuestafichas` AS `Respuestaficha` LEFT JOIN `subpreguntas` AS `Subpregunta` ON (`Respuestaficha`.`cod_subpregunta` = `Subpregunta`.`cod_subpregunta`)  WHERE `Respuestaficha`.`num_evento` = ".$seguimientoinicial['Seguimiento']['num_evento']);
							foreach($respuestas as $respuesta){
	
								//Tipo de familia con la que convive la beneficiaria
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==29)||
										($respuesta['Respuestaficha']['cod_subpregunta']==30)||
										($respuesta['Respuestaficha']['cod_subpregunta']==31)||
										($respuesta['Respuestaficha']['cod_subpregunta']==32)||
										($respuesta['Respuestaficha']['cod_subpregunta']==20101)||
										($respuesta['Respuestaficha']['cod_subpregunta']==20102)||
										($respuesta['Respuestaficha']['cod_subpregunta']==20103)||
										($respuesta['Respuestaficha']['cod_subpregunta']==20104)
									)
								  )
								{
									$datos['tipo_flia'] = $respuesta['Subpregunta']['nom_subpregunta'];
									if($datos['tipo_flia']=="Otros: especificar") $datos['tipo_flia']= "Otros";	
								}
							
								//Estado civil de la banaficiaria.
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==18004)||
										($respuesta['Respuestaficha']['cod_subpregunta']==18005)||
										($respuesta['Respuestaficha']['cod_subpregunta']==18006)||
										($respuesta['Respuestaficha']['cod_subpregunta']==18007)||
										($respuesta['Respuestaficha']['cod_subpregunta']==18008)
									)
								  )
									$datos['estado_civil'] = $respuesta['Subpregunta']['nom_subpregunta'];
							
								//numero de hijos de la beneficiaria.
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&& 
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==2000)||
										($respuesta['Respuestaficha']['cod_subpregunta']==2001)||
										($respuesta['Respuestaficha']['cod_subpregunta']==2002)||
										($respuesta['Respuestaficha']['cod_subpregunta']==2003)||
										($respuesta['Respuestaficha']['cod_subpregunta']==2004)||
										($respuesta['Respuestaficha']['cod_subpregunta']==2005)||
										($respuesta['Respuestaficha']['cod_subpregunta']==2006)||
										($respuesta['Respuestaficha']['cod_subpregunta']==2007)||
										($respuesta['Respuestaficha']['cod_subpregunta']==2008)||
										($respuesta['Respuestaficha']['cod_subpregunta']==2009)
								  	)
								  )
									$datos['num_hijos'] = $respuesta['Subpregunta']['nom_subpregunta'];
								
								//Nivel educacional de la beneficiaria
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==33)||
										($respuesta['Respuestaficha']['cod_subpregunta']==34)||
										($respuesta['Respuestaficha']['cod_subpregunta']==35)||
										($respuesta['Respuestaficha']['cod_subpregunta']==36)||
										($respuesta['Respuestaficha']['cod_subpregunta']==37)||
										($respuesta['Respuestaficha']['cod_subpregunta']==38)||
										($respuesta['Respuestaficha']['cod_subpregunta']==39)||
										($respuesta['Respuestaficha']['cod_subpregunta']==40)
									)
								  )	
								{
									$dimension = $this->Dimension->find('Dimension.cod_dimension = '.$respuesta['Subpregunta']['cod_dimension'],null,null,-1);
									$tipo = $dimension['Dimension']['nom_dimension'];
									if($tipo == "I") $tipo = " ".$tipo; //chanchullo para que quede bien ordenado en el grafico.
									$datos['nivel_educ'] = $respuesta['Subpregunta']['nom_subpregunta']." ".$tipo;
									unset($dimension);
									unset($tipo);
								}
								
								//Trabajo de la beneficiaria
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==42)||
										($respuesta['Respuestaficha']['cod_subpregunta']==43)||
										($respuesta['Respuestaficha']['cod_subpregunta']==44)||
										($respuesta['Respuestaficha']['cod_subpregunta']==45)||
										($respuesta['Respuestaficha']['cod_subpregunta']==46)||
										($respuesta['Respuestaficha']['cod_subpregunta']==47)||
										($respuesta['Respuestaficha']['cod_subpregunta']==20105)
									)
								  )	$datos['trabajo'] = $respuesta['Subpregunta']['nom_subpregunta'];
									
								//Â¿QuiÃ©n es el papa de la guagua?
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==59)||
										($respuesta['Respuestaficha']['cod_subpregunta']==60)||
										($respuesta['Respuestaficha']['cod_subpregunta']==61)||
										($respuesta['Respuestaficha']['cod_subpregunta']==62)
									)
								  ) 
								  	$datos['padre'] = $respuesta['Subpregunta']['nom_subpregunta'];
											
								//Meses de embarazo al llamar primera vez.
								if( ($respuesta['Respuestaficha']['nom_respuesta']==1)&& 
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==1049)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1050)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1051)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1052)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1053)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1054)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1055)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1056)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1057)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1058)
									)
								  )
									$datos['meses'] = $respuesta['Subpregunta']['nom_subpregunta'];
							
								//Metodo anticonceptivo usado
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==49)||
										($respuesta['Respuestaficha']['cod_subpregunta']==50)||
										($respuesta['Respuestaficha']['cod_subpregunta']==51)||
										($respuesta['Respuestaficha']['cod_subpregunta']==510)||
										($respuesta['Respuestaficha']['cod_subpregunta']==52)||
										($respuesta['Respuestaficha']['cod_subpregunta']==53)||
										($respuesta['Respuestaficha']['cod_subpregunta']==20106)
									)
								  )
								{
									if( isset($datos['metodo']) ) $datos['metodo'] = "MÃ¡s de un metodo";
									else $datos['metodo'] = $respuesta['Subpregunta']['nom_subpregunta'];
								}
							
								//A donde fue derivado el caso
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==1061)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1062)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1063)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1064)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1065)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1066)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1067)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1068)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1069)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1070)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1071)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1072)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1073)||
										($respuesta['Respuestaficha']['cod_subpregunta']==1074)
									)
								  )
								{
									if( isset($datos['derivacion']) ) $datos['derivacion'] = "MÃ¡s de un lugar";
									else $datos['derivacion'] = $respuesta['Subpregunta']['nom_subpregunta'];
								}

							}//end foreach respuesta
							
							unset($seguimientoinicial);
							unset($respuestas);
							
							
							//--------Datos de ficha final (seguimiento de cierre)						
							
							$seguimientofinal = $this->Seguimiento->find(array('Seguimiento.cod_caso' => $caso['Caso']['cod_caso'], 'Seguimiento.cod_actividad' => $cod_actividad_cierre),null,'num_evento ASC' ,-1);
							if($seguimientofinal) // hay casos que podrÃ­an estar fallados por razon X.
							{				
								$respuestas = $this->Respuestaficha->findall("Respuestaficha.num_evento = ".$seguimientofinal["Seguimiento"]["num_evento"]);
							
								foreach($respuestas as $respuesta){
									
									//El resultado de la intervencio: mejor, peor, igual, etc.
									if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
										(
											($respuesta['Respuestaficha']['cod_subpregunta']==179)||
											($respuesta['Respuestaficha']['cod_subpregunta']==180)||
											($respuesta['Respuestaficha']['cod_subpregunta']==181)||
											($respuesta['Respuestaficha']['cod_subpregunta']==182)||
											($respuesta['Respuestaficha']['cod_subpregunta']==183)||
											($respuesta['Respuestaficha']['cod_subpregunta']==184)||
											($respuesta['Respuestaficha']['cod_subpregunta']==234)||
											($respuesta['Respuestaficha']['cod_subpregunta']==18001)
										)
									  ) $datos['resultado'] = $respuesta['Subpregunta']['nom_subpregunta'];	
									
									//Nombre de la guagua
									if(	($respuesta['Respuestaficha']['cod_subpregunta']==1059)	) 
										$datos['nombre_guagua'] = $respuesta['Respuestaficha']['nom_respuesta'];
									  
									//Fecha de nacimiento.
									if(	($respuesta['Respuestaficha']['cod_subpregunta']==1060)	) 
										$datos['fecha_nacimiento'] = $respuesta['Respuestaficha']['nom_respuesta'];
									
																	
								}//end foreach respuestas 
		
							}//end if seguimiento final
							
							unset($seguimientofinal);
							unset($respuesta);
							unset($respuestas);
	
	
							//--------Datos de ficha final clinica (seguimiento de cierre clinico)							
	
							$seguimientofinalclinico = $this->Seguimiento->find(array('Seguimiento.cod_caso' => $caso['Caso']['cod_caso'], 'Seguimiento.cod_actividad' => $cod_actividad_clinico),null,'num_evento ASC' ,-1);
							if($seguimientofinalclinico) // hay casos que podrÃ­an estar fallados por razon X.
							{	
								$voluntario_papv = $this->Persona->find("Persona.cod_persona =".$seguimientofinalclinico["Seguimiento"]["cod_voluntario"]);
								$datos['vol_papv'] = $voluntario_papv['Persona']['nom_nombre']." ".$voluntario_papv['Persona']['nom_appat'];
								unset($voluntario_papv);
											
								$respuestas = $this->Respuestaficha->findall("Respuestaficha.num_evento = ".$seguimientofinalclinico["Seguimiento"]["num_evento"]);
								
								foreach($respuestas as $respuesta){
									
									if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
										(	
											($respuesta['Respuestaficha']['cod_subpregunta']==200)||
											($respuesta['Respuestaficha']['cod_subpregunta']==201)||
											($respuesta['Respuestaficha']['cod_subpregunta']==202)
										)
									  ) $datos['cierre_clinico']=$respuesta['Subpregunta']['nom_subpregunta'];
									
									
									if(	($respuesta['Respuestaficha']['cod_subpregunta']==260)	) 
										$datos['asistencias'] = $respuesta['Respuestaficha']['nom_respuesta'];	
									
									if(	($respuesta['Respuestaficha']['cod_subpregunta']==261)	) 
										$datos['inasistencias'] = $respuesta['Respuestaficha']['nom_respuesta'];
		
								}//end foreach respuestas
								
							}//end if seguimiento final clinico
							
							unset($seguimientofinalclinico);
							unset($respuesta);
							unset($respuestas);
												
						}//end if seguimiento inicial
						
					} break;
					
		//----Programa Comunicate
					case '2': {
						$seguimientoinicial = $this->Seguimiento->find(array('Seguimiento.cod_caso' => $caso['Caso']['cod_caso'], 'Seguimiento.cod_actividad' => $cod_actividad_inicial),null,'num_evento ASC' ,-1);
						if($seguimientoinicial) // hay casos que podrÃ­an estar fallados por razon X.
						{
								
						//--------Datos de ficha inicial (seguimiento inicial)
											
							$respuestas = $this->Respuestaficha->query("SELECT `Respuestaficha`.`cod_respuesta`, `Respuestaficha`.`cod_subpregunta`, `Respuestaficha`.`nom_respuesta`, `Subpregunta`.`cod_dimension`, `Subpregunta`.`nom_subpregunta`  FROM `respuestafichas` AS `Respuestaficha` LEFT JOIN `subpreguntas` AS `Subpregunta` ON (`Respuestaficha`.`cod_subpregunta` = `Subpregunta`.`cod_subpregunta`)  WHERE `Respuestaficha`.`num_evento` = ".$seguimientoinicial['Seguimiento']['num_evento']);
																	
							foreach($respuestas as $respuesta){
								
								
								//Tipo de familia con la que convive el beneficiario
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==115)||
										($respuesta['Respuestaficha']['cod_subpregunta']==116)||
										($respuesta['Respuestaficha']['cod_subpregunta']==117)||
										($respuesta['Respuestaficha']['cod_subpregunta']==118)||
										($respuesta['Respuestaficha']['cod_subpregunta']==119)||
										($respuesta['Respuestaficha']['cod_subpregunta']==120)||
										($respuesta['Respuestaficha']['cod_subpregunta']==121)||
										($respuesta['Respuestaficha']['cod_subpregunta']==122)||
										($respuesta['Respuestaficha']['cod_subpregunta']==123)||
										($respuesta['Respuestaficha']['cod_subpregunta']==124)
									)
								  ) 
								{
									$datos['tipo_flia'] = $respuesta['Subpregunta']['nom_subpregunta'];
								  	if($datos['tipo_flia']=="Otros: especificar") $datos['tipo_flia']= "Otros";
								}
								
								//Estado Civil del Beneficiario
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==126)||
										($respuesta['Respuestaficha']['cod_subpregunta']==127)||
										($respuesta['Respuestaficha']['cod_subpregunta']==128)||
										($respuesta['Respuestaficha']['cod_subpregunta']==129)||
										($respuesta['Respuestaficha']['cod_subpregunta']==130)
									)
								  ) $datos['estado_civil'] = $respuesta['Subpregunta']['nom_subpregunta'];
								  
								//Hace cuanto que tiene ese estado civil
								if(	($respuesta['Respuestaficha']['cod_subpregunta']==131)	)
									$datos['anos_estado_civil'] = $respuesta['Respuestaficha']['nom_respuesta'];
																					
								//Nivel educacional del padre de familia
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==141)||
										($respuesta['Respuestaficha']['cod_subpregunta']==142)||
										($respuesta['Respuestaficha']['cod_subpregunta']==143)||
										($respuesta['Respuestaficha']['cod_subpregunta']==144)||
										($respuesta['Respuestaficha']['cod_subpregunta']==145)||
										($respuesta['Respuestaficha']['cod_subpregunta']==146)||
										($respuesta['Respuestaficha']['cod_subpregunta']==147)||
										($respuesta['Respuestaficha']['cod_subpregunta']==148)
									)
								  )
								{
									$dimension = $this->Dimension->find('Dimension.cod_dimension = '.$respuesta['Subpregunta']['cod_dimension'],null,null,-1);
									$tipo = $dimension['Dimension']['nom_dimension'];
									if($tipo == "I") $tipo = " ".$tipo; //chanchullo para que quede bien ordenado en el grafico.
									$datos['educ_padre'] = $respuesta['Subpregunta']['nom_subpregunta']." ".$tipo;
									unset($dimension);
									unset($tipo);
								}
								
								//Nivel educacional del padre de familia
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==153)||
										($respuesta['Respuestaficha']['cod_subpregunta']==154)||
										($respuesta['Respuestaficha']['cod_subpregunta']==155)||
										($respuesta['Respuestaficha']['cod_subpregunta']==156)||
										($respuesta['Respuestaficha']['cod_subpregunta']==157)||
										($respuesta['Respuestaficha']['cod_subpregunta']==158)||
										($respuesta['Respuestaficha']['cod_subpregunta']==159)||
										($respuesta['Respuestaficha']['cod_subpregunta']==160)
									)
								  )
								{
									$dimension = $this->Dimension->find('Dimension.cod_dimension = '.$respuesta['Subpregunta']['cod_dimension'],null,null,-1);
									$tipo = $dimension['Dimension']['nom_dimension'];
									if($tipo == "I") $tipo = " ".$tipo; //chanchullo para que quede bien ordenado en el grafico.
									$datos['educ_madre'] = $respuesta['Subpregunta']['nom_subpregunta']." ".$tipo;
									unset($dimension);
									unset($tipo);
								}
							
								//Trabajo actual del padre de familia
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==149)||
										($respuesta['Respuestaficha']['cod_subpregunta']==150)||
										($respuesta['Respuestaficha']['cod_subpregunta']==151)
									)
								  ) $datos['trabajo_padre'] = $respuesta['Subpregunta']['nom_subpregunta'];
							
								//Trabajo actual de la madre de familia					
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==161)||
										($respuesta['Respuestaficha']['cod_subpregunta']==162)||
										($respuesta['Respuestaficha']['cod_subpregunta']==163)||
										($respuesta['Respuestaficha']['cod_subpregunta']==164)
									)
								  ) $datos['trabajo_madre'] = $respuesta['Subpregunta']['nom_subpregunta'];
						
						
								//Si el beneficiario fue derivado a una instancia externa
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==172)||
										($respuesta['Respuestaficha']['cod_subpregunta']==173)
									)
								  )
								{
									$dimension = $this->Dimension->find('Dimension.cod_dimension = '.$respuesta['Subpregunta']['cod_dimension'],null,null,-1);
									$datos['derivado_si_no'] = $dimension['Dimension']['nom_dimension'];					
									unset($dimension);
								} 
	
								//A que tipo de derivacion fue enviado, mental fisico legal.
								if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
									(
										($respuesta['Respuestaficha']['cod_subpregunta']==174  )||
										($respuesta['Respuestaficha']['cod_subpregunta']==10000)||
										($respuesta['Respuestaficha']['cod_subpregunta']==10001)
									)
								  )	$datos['derivado_tipo'] =  $respuesta['Subpregunta']['nom_subpregunta'];
								
								//A donde especificamente fue derivado
								if(	($respuesta['Respuestaficha']['cod_subpregunta']==175)	)
									$datos['derivado_donde'] = $respuesta['Respuestaficha']['nom_respuesta'];
														
	
							}//end foreach respuestas
						
							//end seccion respuestas seguimiento inicial (simbolico)	
							
							
							unset($seguimientoinicial);
							unset($respuesta);
							unset($respuestas);
							
							
						//--------Datos de ficha final (seguimiento de cierre)						
							$seguimientofinal = $this->Seguimiento->find(array('Seguimiento.cod_caso' => $caso['Caso']['cod_caso'], 'Seguimiento.cod_actividad' => $cod_actividad_cierre),null,'num_evento ASC' ,-1);
							if($seguimientofinal) // hay casos que podrÃ­an estar fallados por razon X.
							{				
								$respuestas = $this->Respuestaficha->findall("Respuestaficha.num_evento = ".$seguimientofinal["Seguimiento"]["num_evento"]);
							
								foreach($respuestas as $respuesta){
									
									//El resultado de la intervencio: mejor, peor, igual, etc.
									if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
										(
											($respuesta['Respuestaficha']['cod_subpregunta']==238)||
											($respuesta['Respuestaficha']['cod_subpregunta']==186)||
											($respuesta['Respuestaficha']['cod_subpregunta']==187)||
											($respuesta['Respuestaficha']['cod_subpregunta']==188)||
											($respuesta['Respuestaficha']['cod_subpregunta']==189)||
											($respuesta['Respuestaficha']['cod_subpregunta']==190)||
											($respuesta['Respuestaficha']['cod_subpregunta']==191)
										)
									  ) $datos['resultado'] = $respuesta['Subpregunta']['nom_subpregunta'];	
									
									//Si asistiÃ³ a la red externa como recomendado al abrir caso.
									if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
										(
											($respuesta['Respuestaficha']['cod_subpregunta']==254)||
											($respuesta['Respuestaficha']['cod_subpregunta']==255)||
											($respuesta['Respuestaficha']['cod_subpregunta']==256)||
											($respuesta['Respuestaficha']['cod_subpregunta']==257)||
											($respuesta['Respuestaficha']['cod_subpregunta']==265)
										)
									  ) $datos['asistencia_derivacion'] = $respuesta['Subpregunta']['nom_subpregunta'];
																	
									
	  
								}//end foreach respuestas 
		
							}//end if seguimiento final
							
							unset($seguimientofinal);
							unset($respuesta);
							unset($respuestas);
	
	
						//--------Datos de ficha final clinica (seguimiento de cierre clinico)							
	
							$seguimientofinalclinico = $this->Seguimiento->find(array('Seguimiento.cod_caso' => $caso['Caso']['cod_caso'], 'Seguimiento.cod_actividad' => $cod_actividad_clinico),null,'num_evento ASC' ,-1);
							if($seguimientofinalclinico) // hay casos que podrÃ­an estar fallados por razon X.
							{	
								$voluntario_papv = $this->Persona->find("Persona.cod_persona =".$seguimientofinalclinico["Seguimiento"]["cod_voluntario"]);
								$datos['vol_papv'] = $voluntario_papv['Persona']['nom_nombre']." ".$voluntario_papv['Persona']['nom_appat'];
								unset($voluntario_papv);
											
								$respuestas = $this->Respuestaficha->findall("Respuestaficha.num_evento = ".$seguimientofinalclinico["Seguimiento"]["num_evento"]);
								
								foreach($respuestas as $respuesta){
	
									if(	($respuesta['Respuestaficha']['nom_respuesta']==1)&&
										(	
											($respuesta['Respuestaficha']['cod_subpregunta']==205)||
											($respuesta['Respuestaficha']['cod_subpregunta']==206)||
											($respuesta['Respuestaficha']['cod_subpregunta']==207)
										)
									  ) $datos['cierre_clinico']=$respuesta['Subpregunta']['nom_subpregunta'];
									
									
									if(	($respuesta['Respuestaficha']['cod_subpregunta']==262)	) 
										$datos['asistencias'] = $respuesta['Respuestaficha']['nom_respuesta'];	
									
									if(	($respuesta['Respuestaficha']['cod_subpregunta']==263)	) 
										$datos['inasistencias'] = $respuesta['Respuestaficha']['nom_respuesta'];
		
								}//end foreach respuestas
								
							}//end if seguimiento final clinico
							
							unset($seguimientofinalclinico);
							unset($respuesta);
							unset($respuestas);
							
						}//end if seguimiento inicial
						
					} break;
					
					
					
		//----otros programas creados a posteriori
					default: {
						
					}
					
				}//end switch programa		
			
			
				$hoja= array_merge ($hoja,array($datos));
				unset($datos);

			}//end foreach caso
			
		//VER SQL
			$programa= $this->Programa->find(array("Programa.cod_programa" => $this->data["Programa"]["cod_programa"]));
			$this->set('type',strtolower($programa["Programa"]["nom_programa"])."-".$this->data["Opcion"]["mes"]."-".$this->data["Opcion"]["periodo"]);
			$this->set('casos',$hoja);
			$this->set('opcion',$this->data["Programa"]["cod_programa"]); 
				
			$this->render('excel', 'excel');
		}
	
		
		
		function modificar()
		{
			$this->escribirHeader("Opciones");
			$programa = $this->data['Programa'];
			$programa['num_frecuencia']=$programa['num_frecuencia']*$this->data['Opcion']['unidad'];
		
		
			if($this->Programa->save($programa)){
				
				$mensaje=14;
			}
			else {
				$mensaje=15;
			}
			
			$this->redirect('programas/index/'.$mensaje);
		}
		
		function agregar()
		{
			$this->escribirHeader("Opciones");
			$programa = $this->data['Programa'];
			$programa['num_frecuencia']=$programa['num_frecuencia']*$this->data['Opcion']['unidad'];
		
		
			if($this->Programa->save($programa)){
				
				$mensaje=16;
			}
			else {
				$mensaje=17;
			}
			
			$this->redirect('programas/index/'.$mensaje);
		}
	}

	
?>
