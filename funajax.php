<?php  session_start(); 

//use PHPMailer\PHPMailer\PHPMailer;
//use PHPMailer\PHPMailer\Exception;

set_time_limit(200); 
require_once "nusoap/lib/nusoap.php";

//nova versao de grava chamada
require_once("c:/xampp/htdocs/-/interrisco/gravanovachamada_sql.php");

date_default_timezone_set('America/Recife');
date_default_timezone_set('America/Recife');
 
//***************************************************************************//
//***                                                                     ***//
//***                       A T E N C A O                                 ***//
//***                                                                     ***//
//***   CASO ESTEJE DEMORANDO O AJAX,  POR:
//***   1 - GET QUE NAO EXISTE
//***   2 - $DEBUG .=  SEM ANTES UM $DEBUG = '';
//***   3 - CHAMAR UMA FUNCAO E NAO PARSSAR O PARAMETRO TIPO FAVISO2(,'TESTE')
//***              
//***************************************************************************//              

/*
@ini_set("display_errors", 1);
@ini_set("log_errors", 1);
@ini_set("error_reporting", E_ALL);

// Reportar E_NOTICE pode ser bom tamb�m (para reportar vari�veis n�o iniciadas
// ou erros de digita��o em nomes de vari�veis ...)
error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
*/

//select * from tusuario where usuario = '123'  = 48813

//select * from tpessoa where cpfcnpj = '01740657950'   873926

//http://www.vivaolinux.com.br/dica/Protegendo-suas-paginas-com-.htaccess-do-Apache

//***************************************************************
//* observacao para converter data em postgresql use  a funcao
//* to_char(current_timestamp, 'DD/MM/YYYY HH24MISS')
//***************************************************************

If ( $_SESSION['usuario'] == '' && $_SESSION['senha']  == '' || $_SESSION['contaprincipal']  == '') {
	
	die("Seu login expirou por favor efetue novo   <a href='index.php'>Login</a> !"); 
} 

require_once('../0funcoes/faviso2.php');

// funcoes do php ajax, acionadas por javascript
// analista de sistemas  Sandro Ransolin
// Programador tb sandro ransolin

// faz a conexao com o banco
require_once('../0funcoes/fconectadba.php');
				
// envia erros do php para o email
require("../0funcoes/manipularerros.php"); 


//date_default_timezone_set('America/Recife');

////date_default_timezone_set("America/Fortaleza");
	
if ( $_GET['sq']	== 'placapesquisaocorrencia' ) {
	
	//abre colchetes na variavel $exibetexto pra o javascript entender que e um array
			
	$sql = 	"
		select 				
			tocorrenciacarro.motivo,
		    tocorrenciacarro.obs,
			tocorrenciacarro.usuariocriacao,
			to_char(tocorrenciacarro.datacriacao,'DD/MM/YY') as datacriacao,
			tocorrenciacarro.bloqueado,cpfcnpjcondutor
		from tocorrenciacarro
		where tocorrenciacarro.placa = '$_GET[placa]'
			order by tocorrenciacarro.datacriacao desc ";		
	
	$resp = pg_exec($sql);

	if ( pg_numrows($resp) > 0 ){
		
		$exibetexto = '[';
				
		for ($i=0; $i < pg_numrows($resp ); $i++) {
 
			$arr=pg_fetch_array($resp,$i,PGSQL_ASSOC);
		
			$exibetexto .= "['$arr[datacriacao]','$arr[usuariocriacao]','$arr[motivo]','$arr[cpfcnpjcondutor]','$arr[obs]','$arr[bloqueado]'],";
			
		}
		
	    // este indica que � um update de pessoa
		$exibetexto .= "['sql','update']]";
		
	}else{
		// apaga os campos para nao ficar lixo
		$exibetexto = "[['sql','insert']]";	
    }
	    
	echo $exibetexto;


//****************
// busca pessoa pelo cpf
//****************				
} else if ( $_GET['sq']	== 'buscapessoa' ) {

	//abre colchetes na variavel $exibetexto pra o javascript entender que e um array
	$exibetexto = '[';		
	
	$sql = 	"
		select 		
			  tpessoa.obsficha,	
			  tpessoa.codipessoa as codipessoa,
			  tpessoafisica.codipessoa as codipessoafisica,
			  cep,
			  trim(cidade) as cidade,
			  uf,
			  endereco,
			  nomepessoa,
			  tpessoa.fone,
			  celular,			  
			  tipopessoa,			  
			  rg,
			  ufrg,			  
			  ufnascimento,
			  trim(cidadenascimento) as cidadenascimento,
			  dtnascimento,
			  datavalidadecnh,
			  cedulacnh,
			  primeirahabilitacao,
			  ufcnh,
			  categoria,
			  numregistro,
			  nomepai,			  
			  tpessoafisica.renach,
			  email,
			  tpessoafisica.numsegurancacnh,
			  nomemae,
			  bairro,
			  numero			  
		from tpessoa  LEFT OUTER JOIN tpessoafisica ON (tpessoa.codipessoa = tpessoafisica.codipessoa) 
		where cpfcnpj = '$_GET[cpfcnpj]'  ";		
	//obs tem que ser left outer join pq se for cpf cadastrado como prorietario nao grava 
	// tpessoa fisica dai quando for fazer select da zebra.
	
	$resp = pg_exec($sql);

	if ( pg_numrows($resp) > 0 ){

		$arr=pg_fetch_array($resp, 0,PGSQL_ASSOC);

		foreach ($arr as $nomedocampo => $auxsqlvalordocampo) {
			
		    //adiciona o nome do campo, valor do campo a variavel exibetexto de modo que o javascript entrenda que e um array
			$exibetexto .= '["'.$nomedocampo.'","'.$auxsqlvalordocampo.'"],';
		
		}
				
	    // este indica que � um update de pessoa
		$exibetexto .= "['sql','update'],";
		
		// eu testo se existe pessoa fisica mesmo, as vezes o cpf � gravado como proprietario
		// ai estara gravado em tpessoa e nao em tpessoafisica, dai quando for fazer a liberacao
		// nao vai trazer os dados de tpessoa fisica e vai dar problema de nao trazer os dados em
		// registros pendentes
		
		// eu coloco dentro do if pg_numrows($resp) > 0, pq fora ele fica procunrando a 
		// $arr['codipessoafisica'] que nao existe e o sistema fica lento
		if ( trim($arr['codipessoafisica']) == '') {
			$exibetexto .= "['sqlpessoafisica','insert']]";
		}else{
			$exibetexto .= "['sqlpessoafisica','update']]";	
		}
		
	}else{
		// apaga os campos para nao ficar lixo
		$exibetexto .= "['bairro',''],['numero',''],['codipessoa',''],['cedulacnh',''],['numsegurancacnh',''],['obsficha',''],['email',''],['cep',''],['cidade','Selecione'],['uf','Selecione'],['endereco',''],['nomepessoa',''],['fone',''],['celular',''],['rg',''],['tipopessoa',''],['ufrg','Selecione'],['ufnascimento','Selecione'],['cidadenascimento',''],['dtnascimento',''],['datavalidadecnh',''],['ufcnh','Selecione'],['primeirahabilitacao',''],['categoria',''],['numregistro',''],['renach',''],['nomepai',''],['nomemae',''],";
  	    $exibetexto .= "['sql','insert'],";	
		$exibetexto .= "['sqlpessoafisica','insert']]";

    }
	
	echo $exibetexto;
	
	
} else if ( $_GET['sq']	== 'historicoserasa' ) {
	
//and			tipo = '$_GET[tiporelatorio]'
	
	$sql = 	"
		select 			
			tserasa.dataentrada,
			tserasa.resposta,			
			tserasa.usuarioqueinseriu,
			tserasa.cpfcnpj,
			tserasa.usuarioresposta,
			tserasa.uf,
			tserasa.custo,
			tserasa.historico,
			tserasa.protocolo,
			tserasa.tipo,
			tconta.nomeconta,
			tpessoa.nomepessoa
		from tserasa,
			tpessoa,
			tconta
		where tserasa.codipessoa = tpessoa.codipessoa and
			tserasa.conta = tconta.conta  ";				
	

	if ($_GET['filtro'] == 'cpf') {
		$sql .= 	"and tserasa.cpfcnpj = '$_GET[cpfcnpj]'  ";
	}	

	if ($_SESSION['conta'] != '48813') {
		$sql .= 	"and tserasa.conta = '$_GET[conta]'	";
	}	else {
		if ($_GET['filtro'] == 'conta')
			$sql .= 	"and tserasa.conta = '$_GET[conta]'	";
		
	}	
	
	$sql .= 	" order by dataentrada desc	";
		
	$resp = pg_exec($sql);
	
	if ( pg_numrows($resp) > 0 ){
  
		$exibetexto = "<hr><table width='100%' border=1>";
		$exibetexto .="<tr><td></td><td class='menuiz_botonoff'> Data Entrada </td>
			<td class='menuiz_botonoff'> Conta</td>
			<td class='menuiz_botonoff'> CpfCnpj</td>
			<td class='menuiz_botonoff'> Uf </td>
			<td class='menuiz_botonoff'> Nomepessoa </td>			
			<td class='menuiz_botonoff'> Tipo </td>
			<td class='menuiz_botonoff'> Historico</td>
			<td class='menuiz_botonoff'> Resposta</td>			
			</tr>";
  				
        for ($i=0; $i < pg_numrows($resp ); $i++) {
 
			$arr=pg_fetch_array($resp,$i,PGSQL_ASSOC);
		  
			$exibetexto .="<tr  class='titulorojo'><td></td><td> $arr[dataentrada] </td>
				<td>$arr[nomeconta]</td>
				<td>$arr[cpfcnpj]</td>
				<td>$arr[uf]</td> 
				<td>$arr[nomepessoa]</td> 				
				<td>$arr[tipo]</td> 
				<td>$arr[historico]  </td>
				<td class='menuiz_botonoff'> <a href='' onclick=\"window.open('formularioserasaedita.php?&cpfcnpj=$arr[cpfcnpj]&protocolo=$arr[protocolo]&respostaaux=$arr[resposta]','','width=1000,height=700')\">Resposta</a> $arr[resposta] </td> 				
				";
				
			$exibetexto .="<tr><td colspan=5> </td></tr>";
			
		}
		
		//<td class='botonoff' width='16%'><a href='#' onclick=inserereboque('$arr[placa]','$arr[codipessoa]');><div id='buttonaz'><img src='../0bmp/reboque.png'  width='25' height='25'>Selecionar</div></a></td></tr>";

		$exibetexto .= "</table>";
		echo $exibetexto;
		
    }else{
	
		echo "<tr><td class=menuiz_botonoff><fieldset><legend><b>Mensagem </b></legend> <img src='../0bmp/neg.png'  width='45' height='35'> Registro nao encontrado !  </fieldset></td></tr>";	
	
	}	
	
	//echo $sql." teste $_GET[tiporelatorio] ";

	
}else if ( $_GET['sq']	== 'buscapessoaConsultaEstadual' ) {

	//abre colchetes na variavel $exibetexto pra o javascript entender que e um array
	$exibetexto = '[';	
	
	$sqlcusto = "
		select 
			custopesquisa,
			custordosp,
			custordosc,
			custordopr,
			custordogo,
			custordomg,
			custordope,
			custordorj,
			custordors,
			custordoac  ,
			 custordoam  ,
			 custordoap  ,
			 custordoba  ,
			 custordoce  ,
			 custordodf  ,
			 custordoes  ,
			 custordoma  ,
			 custordomt  ,
			 custordopa  ,
			 custordopb  ,
			 custordope  ,
			 custordopi  ,
			 custordorn  ,
			 custordoro  ,
			 custordorr  ,
			 custordose  ,
			 custordoto  ,
			custordoms,
			custosocialcompleta
		from tparametrocadastro
		where tparametrocadastro.conta = '$_GET[conta]'	";
	
	$rescusto = pg_exec($sqlcusto);
	
	If (pg_numrows($rescusto) > 0) {
 
		$arrcusto=pg_fetch_array($rescusto, 0,PGSQL_ASSOC);

		foreach ($arrcusto as $nomedocampo => $auxsqlvalordocampo) {
			
		    //adiciona o nome do campo, valor do campo a variavel exibetexto de modo que o javascript entrenda que e um array
			$exibetexto .= '["'.$nomedocampo.'","'.$auxsqlvalordocampo.'"],';
		
		}
			
	}
	
	$sql = 	"
		select 		
				tpessoa.codipessoa as codipessoa, 
				tpessoa.nomepessoa,
				tpessoa.uf,
              tpessoafisica.codipessoa as codipessoafisica,      			  			  
			  tpessoafisica.rg,
			  tpessoafisica.ufrg,			  			  
			  tpessoa.obsficha,	
			  tpessoafisica.nomemae
		from tpessoa  LEFT OUTER JOIN tpessoafisica ON (tpessoa.codipessoa = tpessoafisica.codipessoa) 
		where cpfcnpj = '$_GET[cpfcnpj]'  ";		
	//obs tem que ser left outer join pq se for cpf cadastrado como prorietario nao grava 
	// tpessoa fisica dai quando for fazer select da zebra.
	
	$resp = pg_exec($sql);

	if ( pg_numrows($resp) > 0 ){

		$arr=pg_fetch_array($resp, 0,PGSQL_ASSOC);

		foreach ($arr as $nomedocampo => $auxsqlvalordocampo) {
			
		    //adiciona o nome do campo, valor do campo a variavel exibetexto de modo que o javascript entrenda que e um array
			$exibetexto .= '["'.$nomedocampo.'","'.$auxsqlvalordocampo.'"],';
		
		}
				
	    // este indica que � um update de pessoa
		$exibetexto .= "['sql','update'],";
		
		// eu testo se existe pessoa fisica mesmo, as vezes o cpf � gravado como proprietario
		// ai estara gravado em tpessoa e nao em tpessoafisica, dai quando for fazer a liberacao
		// nao vai trazer os dados de tpessoa fisica e vai dar problema de nao trazer os dados em
		// registros pendentes
		
		// eu coloco dentro do if pg_numrows($resp) > 0, pq fora ele fica procunrando a 
		// $arr['codipessoafisica'] que nao existe e o sistema fica lento
		if ( trim($arr['codipessoafisica']) == '') {
			$exibetexto .= "['sqlpessoafisica','insert']]";
		}else{
			$exibetexto .= "['sqlpessoafisica','update']]";	
		}
		
		
	}else{
				
		// apaga os campos para nao ficar lixo
		$exibetexto .= "['codipessoa',''],['nomepessoa',''],['uf','Selecione'],";
  	    $exibetexto .= "['sql','insert'],";	
		$exibetexto .= "['sqlpessoafisica','insert']]";

    }
	
	echo $exibetexto;
	

} else if ( $_GET['sq']	== 'salvapessoaconsultaestadual' ) {
	
    //ini_set('default_charset','UTF-8');
    // coloca em maiusculo
	
    $_GET['nomepessoa'] = strtoupper($_GET['nomepessoa']);
    $_GET['rg'] = strtoupper($_GET['rg']);
	$_GET['nomemae'] = strtoupper($_GET['nomemae']);
	
	$nomecampo = '';
	$auxsqlvalor = ''; 
	$sql = '';
	$debug  = 'incio debug<br>';
			
	if ($_GET['sql'] == 'update') {
	
		$sql = "
			
			BEGIN; update  tpessoa
			set 
				nomepessoa = '$_GET[nomepessoa]',				
				usuarioalteracao = '$_SESSION[usuario]',
				usuarioalteracaodata =  '".date('d/m/Y H:i:s')."',
				obsficha = '$_GET[obsficha]'
			where codipessoa = '$_GET[codipessoa]'  ; ";	
	
		// a verificacao se necessita update ou insert em tpessoa fisica � pq tpessoa pode ter sido
		// cadastrado como proprietario, nete caso nao foi gravado em tpessoafisica.
		// portanto existem casos de gravacao em tpessoa e nao gravado em tpessoafisica.
		
		if ($_GET['sqlpessoafisica'] == 'update' ) { 

			$nomecampo = '';
			$auxsqlvalor = ''; 

			$sql  .= "
				update  tpessoafisica
				set 
					rg = '$_GET[rg]',
					ufrg = '$_GET[ufrg]',										
					nomemae = '$_GET[nomemae]'
					$auxsqlvalor
				where codipessoa = '$_GET[codipessoa]'  ;
				
				COMMIT;	 ";	
								
		}else {

			$nomecampo = '';
			$auxsqlvalor = ''; 

			$sql .= "
				insert into tpessoafisica(codipessoa,rg,ufrg,nomemae $nomecampo)
				values('$_GET[codipessoa]','$_GET[rg]','$_GET[ufrg]','$_GET[nomemae]' $auxsqlvalor) ;
				COMMIT; ";		

		}		
		
	//	$debug  .= "<br> $sql <br>";			
		$resp = pg_exec($sql);			
		
	// senao � insert
	} else {

		$_GET['codipessoa'] = fmaxcodipessoa();
		
		$nomecampo = '';
		$auxsqlvalor = ''; 
		
		$sql = "
					
			BEGIN; insert into  tpessoa (copiadoc,obsficha,cpfcnpj,usuario,usuariodatacadastrouficha,codipessoa,nomepessoa,tipopessoa)
			values(' ','$_GET[obsficha]','$_GET[cpfcnpj]','$_SESSION[usuario]','".date('d/m/Y H:i:s')."','$_GET[codipessoa]','$_GET[nomepessoa]','CPF');			

			insert into  tpessoafisica(codipessoa,rg,ufrg,nomemae $nomecampo)
			values('$_GET[codipessoa]','$_GET[rg]','$_GET[ufrg]','$_GET[nomemae]' $auxsqlvalor); 

			COMMIT; ";		
		
		$resp = pg_exec($sql);	
		
	} 
		
	echo "[['codipessoa','$_GET[codipessoa]']]";
  	
            
        
} else if ( $_GET['sq']	== 'buscacep' ) {

//abre colchetes na variavel $exibetexto pra o javascript entender que e um array
	$exibetexto = '[';		
	
	$sql = 	"
		select 	
			cep,	
			bairro,
			trim(uf) as uf,				
			trim(cidade) as cidade,
			endereco					
		from tcep
		where cep = '$_GET[cep]'  ";		
		
	$resp = pg_exec($sql);

	if ( pg_numrows($resp) > 0 ){

		$arr=pg_fetch_array($resp, 0,PGSQL_ASSOC);

		foreach ($arr as $nomedocampo => $auxsqlvalordocampo) {
			
		    //adiciona o nome do campo, valor do campo a variavel exibetexto de modo que o javascript entrenda que e um array
			$exibetexto .= '["'.$nomedocampo.'","'.$auxsqlvalordocampo.'"],';
		
		}
							
		$exibetexto .= "]";
	
	}
	
	echo $exibetexto;
			
} else if ( $_GET['sq']	== 'gravaserasa' ) {

	$campo = '';
	$valor = '';

	// vejo se ja tem rdo para nao ficar duplicando cobranca
	
	// procurordo(cpf, uf do rdo a pesquisar) declarado abaixo
	// se ja tem rdo entao nao manda para a pesquisa
	$obs = procurordo($_GET[cpfcnpj],$_GET[uf]);
	
	//se ainda nao tem nenhuma pesquisa rdo mando pesquisar
	if ( $obs == '') {
		
		//gravo que to enviando para o civil e libero email para o envio civil
		$enviarconsultoria = 't';	
		$campo	= 'datenviocivil,usuarioenviocivil,';
		$valor = "'".date('d/m/y H:i')."','".$_SESSION[usuario]."',";
		$statusprotocolo = 2; // ja foi mexido alguma coisa
		
	}else{
		
		//negativo o envio do civil
		$enviarconsultoria = 'f';
		$statusprotocolo = 1; //rever
		
	}	

	$protocolo = date('dmyHis').substr(rand(),0,2);				
		
	$sql .= "insert into tserasa($campo codipessoa,custo,uf,dataentrada,resposta,contaprincipal,conta,usuarioqueinseriu,statusprotocolo,cpfcnpj,tipocpfcnpj,historico,tipo)
		values($valor '$_GET[codipessoa]','$_GET[custo]','$_GET[uf]','".date('d/m/y H:i')."',';','$_GET[contaprincipal]','$_GET[conta]','$_SESSION[usuario]','$statusprotocolo','$_GET[cpfcnpj]','$_GET[tipocpfcnpj]','$_GET[historico]','$_GET[tipo]');	";		

    $resp = pg_exec($sql);			
			
	echo "$enviarconsultoria";
	//echo $sql." serasa ".$sqlserasa." custo $custo"." historico ".$_GET['historico'];
	
		
} else if ( $_GET['sq']	== 'gravapessoa' ) {
	
//ini_set('default_charset','UTF-8');

    // coloca em maiusculo
	
    $_GET['endereco'] = strtoupper($_GET['endereco']);
    $_GET['nomepessoa'] = strtoupper($_GET['nomepessoa']);
    $_GET['rg'] = strtoupper($_GET['rg']);
    $_GET['nomepai'] = strtoupper($_GET['nomepai']);    
	$_GET['nomemae'] = strtoupper($_GET['nomemae']);
	$_GET['categoria'] = strtoupper($_GET['categoria']);
	$_GET['bairro'] = strtoupper($_GET['bairro']);

	//$_GET['obsficha'] = RemoveAcentos($_GET['obsficha']);
	
	$nomecampo = '';
	$auxsqlvalor = ''; 
	$sql = '';
	$debug  = 'incio debug<br>';
		
	// se o celular nao foi colocado, insere zero para nao dar problema na gravacao
	
	if ( ($_GET['cidadenascimento'] == 	'Selecione') or ($_GET['cidadenascimento'] == 'Selecione a UF') or ($_GET['cidadenascimento'] == '') ) {	

		// o usuario pode deixar de digitar dai tem que colocar o padrao zero pois o campo � numerico
		$_GET['ufnascimento'] = 'AC';
		$_GET['cidadenascimento'] = 'ASSIS BRASIL';
		
	}
	
	
	if ( strlen($_GET['celular']) < 9 ) {	

		// o usuario pode deixar de digitar dai tem que colocar o padrao zero pois o campo � numerico
		$_GET['celular'] = '0';
		
	}
	
	// se e uma insercao pega o maxcodipessoa
		
	if ($_GET['sql'] == 'update') {
	
		$sql = "
			
			BEGIN; update  tpessoa
			set 
				cep = '$_GET[cep]',
				cidade = '$_GET[cidade]',
				uf = '$_GET[uf]',
				endereco = '$_GET[endereco]',
				nomepessoa = '$_GET[nomepessoa]',
				fone = '$_GET[fone]',
				bairro = '$_GET[bairro]',
				numero = '$_GET[numero]',
				celular = '$_GET[celular]',							
				email = '$_GET[email]',
				usuarioalteracao = '$_SESSION[usuario]',
				usuarioalteracaodata =  '".date('d/m/Y H:i:s')."',
				tipopessoa = '$_GET[tipopessoa]',
				obsficha = '$_GET[obsficha]'
			where codipessoa = '$_GET[codipessoa]'  ; ";	
	
		// a verificacao se necessita update ou insert em tpessoa fisica � pq tpessoa pode ter sido
		// cadastrado como proprietario, nete caso nao foi gravado em tpessoafisica.
		// portanto existem casos de gravacao em tpessoa e nao gravado em tpessoafisica.
		
		if ($_GET['sqlpessoafisica'] == 'update' ) { 

			$nomecampo = '';
			$auxsqlvalor = ''; 

			if ( strlen($_GET['numregistro']) > 3 ) {	
				$auxsqlvalor .= " ,numregistro = '$_GET[numregistro]' ";
			}
			if ( strlen($_GET['datavalidadecnh']) > 7 ) {	
				$auxsqlvalor .= " ,datavalidadecnh = '$_GET[datavalidadecnh]' ";
			}

			if ( strlen($_GET['ufcnh']) > 1 ) {	
				$auxsqlvalor .= " ,ufcnh = '$_GET[ufcnh]' ";
			}
			
			if ( strlen($_GET['categoria']) > 0 ) {	
				$auxsqlvalor .= " ,categoria = '$_GET[categoria]' ";
			}

			if ( strlen($_GET['primeirahabilitacao']) > 0 ) {	
				$auxsqlvalor .= " ,primeirahabilitacao = '$_GET[primeirahabilitacao]' ";
			}

			if ( strlen($_GET['renach']) > 0 ) {	
				$auxsqlvalor .= " ,renach = '$_GET[renach]' ";
			}
			
			$sql  .= "
				update  tpessoafisica
				set 
					rg = '$_GET[rg]',
					ufrg = '$_GET[ufrg]',
					ufnascimento = '$_GET[ufnascimento]',
					cidadenascimento = '$_GET[cidadenascimento]',
					dtnascimento = '$_GET[dtnascimento]',										
					nomepai = '$_GET[nomepai]',
					numsegurancacnh = '$_GET[numsegurancacnh]',
					cedulacnh = '$_GET[cedulacnh]',
					nomemae = '$_GET[nomemae]'
					$auxsqlvalor
				where codipessoa = '$_GET[codipessoa]'  ;
				
				COMMIT;	 ";	
								
		}else {

			$nomecampo = '';
			$auxsqlvalor = ''; 

			if ( strlen($_GET['numregistro']) > 3 ) {	
				$nomecampo .= ',numregistro';
				$auxsqlvalor .= " ,'$_GET[numregistro]' ";
			}
			if ( strlen($_GET['datavalidadecnh']) > 7 ) {	
				$nomecampo .= ',datavalidadecnh';
				$auxsqlvalor .= " ,'$_GET[datavalidadecnh]' ";
			}

			if ( strlen($_GET['ufcnh']) == 2  ) {	
				$nomecampo .= ',ufcnh';
				$auxsqlvalor .= " ,'$_GET[ufcnh]' ";
			}
			
			if ( strlen($_GET['categoria']) > 0 ) {	
				$nomecampo .= ',categoria';
				$auxsqlvalor .= " ,'$_GET[categoria]' ";
			}
		
			if ( strlen($_GET['primeirahabilitacao']) > 0 ) {	
				$nomecampo .= ',primeirahabilitacao';
				$auxsqlvalor .= " ,'$_GET[primeirahabilitacao]' ";
			}
			
			if ( strlen($_GET['renach']) > 0 ) {	
				$nomecampo .= ',renach';
				$auxsqlvalor .= " ,'$_GET[renach]' ";
			}
			
			$sql .= "
				insert into tpessoafisica(cedulacnh,numsegurancacnh,codipessoa,rg,ufrg,ufnascimento,cidadenascimento,dtnascimento,nomepai,nomemae $nomecampo)
				values('$_GET[cedulacnh]','$_GET[numsegurancacnh]','$_GET[codipessoa]','$_GET[rg]','$_GET[ufrg]','$_GET[ufnascimento]','$_GET[cidadenascimento]','$_GET[dtnascimento]','$_GET[nomepai]','$_GET[nomemae]' $auxsqlvalor) ;
				
				COMMIT; ";		

		}		
		
		$debug  .= "<br> $sql <br>";			
		$resp = pg_exec($sql);			
		
	// senao � insert
	} else {

		$_GET['codipessoa'] = fmaxcodipessoa();
		
		if ($_GET['codipessoa'] == '' ) {
			$_GET['codipessoa'] = 1;
		}

		$nomecampo = '';
		$auxsqlvalor = ''; 

		if ( strlen($_GET['numregistro']) > 3 ) {	
			$nomecampo .= ',numregistro';
			$auxsqlvalor .= " ,'$_GET[numregistro]' ";
		}
		if ( strlen($_GET['datavalidadecnh']) > 7 ) {	
			$nomecampo .= ',datavalidadecnh';
			$auxsqlvalor .= " ,'$_GET[datavalidadecnh]' ";
		}

		if ( strlen($_GET['ufcnh']) == 2  ) {	
			$nomecampo .= ',ufcnh';
			$auxsqlvalor .= " ,'$_GET[ufcnh]' ";
		}
		
		if ( strlen($_GET['categoria']) > 0 ) {	
			$nomecampo .= ',categoria';
			$auxsqlvalor .= " ,'$_GET[categoria]' ";
		}
		
		if ( strlen($_GET['primeirahabilitacao']) > 0 ) {	
			$nomecampo .= ',primeirahabilitacao';
			$auxsqlvalor .= " ,'$_GET[primeirahabilitacao]' ";
		}
	
		if ( strlen($_GET['renach']) > 0 ) {	
			$nomecampo .= ',renach';
			$auxsqlvalor .= " ,'$_GET[renach]' ";
		}
		
		$sql = "
			
			BEGIN; insert into  tpessoa (copiadoc,bairro,numero,obsficha,cpfcnpj,usuario,usuariodatacadastrouficha,email,codipessoa,cep,cidade,uf,endereco,nomepessoa,fone,celular,tipopessoa)
			values(' ','$_GET[bairro]','$_GET[numero]','$_GET[obsficha]','$_GET[cpfcnpj]','$_SESSION[usuario]','".date('d/m/Y H:i:s')."','$_GET[email]','$_GET[codipessoa]','$_GET[cep]','$_GET[cidade]','$_GET[uf]','$_GET[endereco]','$_GET[nomepessoa]','$_GET[fone]','$_GET[celular]','CPF');			

			insert into  tpessoafisica(cedulacnh,numsegurancacnh,codipessoa,rg,ufrg,ufnascimento,cidadenascimento,dtnascimento,nomepai,nomemae $nomecampo)
			values('$_GET[cedulacnh]','$_GET[numsegurancacnh]','$_GET[codipessoa]','$_GET[rg]','$_GET[ufrg]','$_GET[ufnascimento]','$_GET[cidadenascimento]','$_GET[dtnascimento]','$_GET[nomepai]','$_GET[nomemae]' $auxsqlvalor); 

			COMMIT; ";		
		
		$resp = pg_exec($sql);	
		
	} 
		
	///////////////////////////////	
	//migracao lideransat
	///////////////////////////////	
		
		
		
	//tenho que retornar o codigo pessoa, para informar na referencia o codipessoa quando for insert (NOVO CADASTRO DE MOTORISTA)
	if  ($resp) {	
		echo "[['codipessoa','$_GET[codipessoa]']]";
	}else{
		echo "[['codipessoa','']]";
	}
	
} else if ( $_GET['sq']	== 'buscarefpessoa' ) {

//******************
// vou ter que arrumar esse para vir as referencias quando digitar o cpf
//***********************************

	//abre colchetes na variavel $exibetexto pra o javascript entender que e um array
	$exibetexto = '[';		
	
	$sql = 	"
		select
			nome,
			trim(cidade) as cidade,
			uf,
			contato,
			treferencia.codireferencia as codireferencia
		from treferencia,
			treferenciapessoa
		where codipessoa = $_GET[codipessoa] order by codireferencia desc ";		
		
	$resp = pg_exec($sql);
	
	if ( pg_numrows($resp) > 0 ){

		$arr=pg_fetch_array($resp, 0,PGSQL_ASSOC);

		foreach ($arr as $nomedocampo => $auxsqlvalordocampo) {
			
		    //adiciona o nome do campo, valor do campo a variavel exibetexto de modo que o javascript entrenda que e um array
			$exibetexto .= '["'.$_GET['ref'].$nomedocampo.'","'.$auxsqlvalordocampo.'"],';
		
		}
		
	    //como ja preencheu os dados fecha o colchetes na variavel $exibetexto para o javascript entender o array
  	    $exibetexto .= "['$_GET[ref]sql','update']]";

	}else{
		// apaga os campos para nao ficar lixo
		$exibetexto .= "['$_GET[ref]nome',''],['$_GET[ref]cidade',''],['$_GET[ref]uf',''],['$_GET[ref]contato',''],['$_GET[ref]codireferencia',''],";
  	    $exibetexto .= "['$_GET[ref]sql','insert']]";	
    }
	
	echo $exibetexto;
	
} else if ( $_GET['sq']	== 'buscaref' ) {

	//abre colchetes na variavel $exibetexto pra o javascript entender que e um array
	$exibetexto = '[';		
	
	$sql = 	"
		select
			nome,
			trim(cidade) as cidade,
			uf,
			contato,
			codireferencia
		from treferencia
		where fone = $_GET[fone] ";	
		
	$resp = pg_exec($sql);
	
	if ( pg_numrows($resp) > 0 ){

		$arr=pg_fetch_array($resp, 0,PGSQL_ASSOC);

		foreach ($arr as $nomedocampo => $auxsqlvalordocampo) {
			
		    //adiciona o nome do campo, valor do campo a variavel exibetexto de modo que o javascript entrenda que e um array
			$exibetexto .= '["'.$_GET['ref'].$nomedocampo.'","'.$auxsqlvalordocampo.'"],';
		
		}
		
	    //como ja preencheu os dados fecha o colchetes na variavel $exibetexto para o javascript entender o array
  	    $exibetexto .= "['$_GET[ref]sql','update']]";

	}else{
		// apaga os campos para nao ficar lixo
		$exibetexto .= "['$_GET[ref]nome',''],['$_GET[ref]cidade',''],['$_GET[ref]uf',''],['$_GET[ref]contato',''],['$_GET[ref]codireferencia',''],";
  	    $exibetexto .= "['$_GET[ref]sql','insert']]";	
    }
	
	echo $exibetexto;
		
} else if ( $_GET['sq']	== 'gravaref' ) {

    $_GET['nome'] = strtoupper($_GET['nome']);
    $_GET['contato'] = strtoupper($_GET['contato']);
		
	if 	($_GET['sql'] == 'insert') {

		$maxcodireferencia = fmaxcodireferencia();		
		if ($maxcodireferencia == '') $maxcodireferencia = 1; 
		if ($maxcodireferencia == '0') $maxcodireferencia = 1; 
		
		$sql = "
			insert into  treferencia (fone,nome,contato,cidade,uf,codireferencia,obs)
			values('$_GET[fone]','$_GET[nome]','$_GET[contato]','$_GET[cidade]','$_GET[uf]','$maxcodireferencia','($_SESSION[usuario]);')";		
			
		$sqlpf = "
			insert into  treferenciapessoa(codipessoa,codireferencia)
			values('$_GET[codipessoa]','$maxcodireferencia')";		
				    			  
		$resp = pg_exec($sql);	
		$resppf = pg_exec($sqlpf);	

	} else if ($_GET['sql'] == 'update') {

		// faz um update em treferencia
		
		$sql = "
			update  treferencia
			set 
				fone = '$_GET[fone]',
				cidade = '$_GET[cidade]',
				uf = '$_GET[uf]',
				nome = '$_GET[nome]',
				contato = '$_GET[contato]'
			where codireferencia = '$_GET[codireferencia]' ";	

		// verifica se no treferenciapessoa	
			
		$refpessoa = "select codipessoa 
						from treferenciapessoa 
						where codipessoa = $_GET[codipessoa] and codireferencia = $_GET[codireferencia] ";	

		$resprefpessoa = pg_exec($refpessoa);
			
		if ( pg_numrows($resprefpessoa) < 1 ){

			$sqlpf = "insert into  treferenciapessoa(codipessoa,codireferencia)
						values('$_GET[codipessoa]','$_GET[codireferencia]')";		
						
			$resposta = pg_exec($sqlpf);

		}
			
		$resp = pg_exec($sql);			
		//$resppf = true;
	}
	
	echo "codigo das referencias <br> $sql <br> $sqlpf <br> $_GET[sql]";

		
} else if ( $_GET['sq']	== 'buscaproprietario' ) {

	//abre colchetes na variavel $exibetexto pra o javascript entender que e um array
	$exibetexto = '[';		
	
	$sql = 	"
		select 			
			  tpessoa.codipessoa as codipessoa,
			  cep,
			  trim(cidade) as cidade,
			  uf,
			  endereco,
			  nomepessoa,
			  fone,
			  celular,
			  tipopessoa
		from tpessoa  
		where cpfcnpj = '$_GET[cpfcnpj]'  ";		
		
	$resp = pg_exec($sql);

	if ( pg_numrows($resp) > 0 ){

		$arr=pg_fetch_array($resp, 0,PGSQL_ASSOC);

		foreach ($arr as $nomedocampo => $auxsqlvalordocampo) {
			
		    //adiciona o nome do campo, valor do campo a variavel exibetexto de modo que o javascript entrenda que e um array
			$exibetexto .= '["'.$nomedocampo.'","'.$auxsqlvalordocampo.'"],';
		
		}
		
	    //como ja preencheu os dados fecha o colchetes na variavel $exibetexto para o javascript entender o array
  	    $exibetexto .= "['sql','update']]";

	}else{
		// apaga os campos para nao ficar lixo
  	    $exibetexto .= '["codipessoa",""],["cep",""],["cidade","Selecione"],["uf","Selecione"],["endereco",""],["nomepessoa",""],["fone",""],["celular",""],["tipopessoa","Selecione"],';
  	    $exibetexto .= "['sql','insert']]";	
    }
	
	echo $exibetexto;
	
} else if ( $_GET['sq']	== 'buscaproprietario_importacaoficha' ) {

	//abre colchetes na variavel $exibetexto pra o javascript entender que e um array
	$exibetexto = '[';		
	
	$sql = 	"
		select 			
			  tpessoa.codipessoa as codipessoa,
			  cep,
			  trim(cidade) as cidade,
			  uf,
			  endereco,
			  nomepessoa,
			  fone,
			  celular,			  
			  tipopessoa
		from tpessoa  
		where cpfcnpj = '$_GET[cpfcnpj]'  ";		
		
	$resp = pg_exec($sql);

	if ( pg_numrows($resp) > 0 ){

		$arr=pg_fetch_array($resp, 0,PGSQL_ASSOC);

		foreach ($arr as $nomedocampo => $auxsqlvalordocampo) {
			
		    //adiciona o nome do campo, valor do campo a variavel exibetexto de modo que o javascript entrenda que e um array
			$exibetexto .= '["'.$nomedocampo.'","'.$auxsqlvalordocampo.'"],';
		
		}
		
	    //como ja preencheu os dados fecha o colchetes na variavel $exibetexto para o javascript entender o array
  	    $exibetexto .= "['sql','update']]";

	}else{
		// apaga os campos para nao ficar lixo
  	    $exibetexto .= "['sql','insert']]";	
    }
	
	echo $exibetexto;	
	
	
} else if ( $_GET['sq']	== 'buscaplaca' ) {

    $_GET['placa'] = strtoupper($_GET['placa']);

	//abre colchetes na variavel $exibetexto pra o javascript entender que e um array
	$exibetexto = '[';		
	
	$sql = 	"
		select			
			marca,
			modelo,
			ufplaca,
			renavan,
			chassi,
			antt,
			cor,
			anofabricacao,
			tcarro.tara,
			tcarro.capacidadecargakg,
			tcarro.capacidadecargam3,
			tcarro.tipocarroceria,
			tcarro.cpfcnpjtransportador,
			tcarro.nometransportador,
			tpessoa.codipessoa as codipessoa,
			cep,
			cidade,
			uf,
			cpfcnpj,
			endereco,
			nomepessoa,
			categoria,
			fone,
			celular,
			numeroseguranca,
			tipopessoa
		from tcarro,
			tpessoa	
		where placa = '$_GET[placa]' and 
			tcarro.codipessoa = tpessoa.codipessoa ";		
		
	$resp = pg_exec($sql);
	
	if ( pg_numrows($resp) > 0 ){

		$arr=pg_fetch_array($resp, 0,PGSQL_ASSOC);

		foreach ($arr as $nomedocampo => $auxsqlvalordocampo) {
			
		    //adiciona o nome do campo, valor do campo a variavel exibetexto de modo que o javascript entrenda que e um array
			$exibetexto .= '["'.$nomedocampo.'","'.$auxsqlvalordocampo.'"],';
		
		}
		
	    //como ja preencheu os dados fecha o colchetes na variavel $exibetexto para o javascript entender o array
  	    $exibetexto .= "['sql','update'],['sqlplaca','update']]";

	}else{
		// apaga os campos para nao ficar lixo
		$exibetexto .= "['categoria',''],['numeroseguranca',''],['cep',''],['cidade','Selecione'],['uf','Selecione'],['endereco',''],['nomepessoa',''],['fone',''],['celular',''],['tipopessoa',''],['codipessoa',''],['marca',''],['modelo',''],['ufplaca',''],['renavan',''],['chassi',''],['anofabricacao',''],['antt',''],['cor',''],['cpfcnpj',''],";
  	    $exibetexto .= "['sql','insert'],['sqlplaca','insert']]";	
    }
	
	echo $exibetexto;
			
} else if ( $_GET['sq']	== 'gravaplaca' ) {

   $auxsqlinsere = '';
   $auxsqlvalor = '';

//Descri��o das categorias
//Categoria A - habilita a condu��o de ve�culo motorizado de duas ou tr�s rodas, com ou sem carro lateral (motos, triciclos etc);
//Categoria B - habilita a condu��o de ve�culo motorizado, n�o abrangido pela categoria A, cujo peso bruto total n�o exceda a tr�s mil e quinhentos quilogramas e cuja lota��o n�o exceda a oito lugares, exclu�do o do motorista (carros de passeio);
//Categoria C - habilita a condu��o de ve�culo motorizado utilizado em transporte de carga, cujo peso bruto total exceda a tr�s mil e quinhentos quilogramas (caminh�es);
//Categoria D - transporte de passageiros, cuja lota��o exceda a oito lugares, exclu�do o do motorista (�nibus);
//Categoria E - condutor de combina��o de ve�culos em que a unidade tratora se enquadre nas categorias B, C ou D e cuja unidade acoplada, reboque, semi-reboque ou articulada, tenha seis mil quilogramas ou mais de peso bruto total, ou cuja lota��o exceda a oito lugares, ou, ainda, seja enquadrado na categoria trailer.

    $_GET['chassi'] = strtoupper($_GET['chassi']);
    $_GET['placa'] = strtoupper($_GET['placa']);
    $_GET['marca'] = strtoupper($_GET['marca']);
    $_GET['modelo'] = strtoupper($_GET['modelo']);
    $_GET['endereco'] = strtoupper($_GET['endereco']);
    $_GET['nomepessoa'] = strtoupper($_GET['nomepessoa']);
			
	if ( strlen($_GET['celular']) < 9 ) {	

		// o usuario pode deixar de digitar dai tem que colocar o padrao zero pois o campo � numerico
		$_GET['celular'] = '0';
		
	}
				
	/*
			BEGIN; insert into  tpessoa (copiadoc,obsficha,cpfcnpj,usuario,usuariodatacadastrouficha,codipessoa,nomepessoa,tipopessoa)
			values(' ','$_GET[obsficha]','$_GET[cpfcnpj]','$_SESSION[usuario]','".date('d/m/Y H:i:s')."','$_GET[codipessoa]','$_GET[nomepessoa]','CPF');			

	
	*/			
				
	if 	($_GET['sql'] == 'insert') {

		$_GET['codipessoa'] = fmaxcodipessoa();
			
		$auxsqlinsere = '';
		$auxsqlvalor = '';
   
		if ( trim($_GET['cep'])  != '') {	
			$auxsqlinsere .= ",cep";
			$auxsqlvalor .= ",'$_GET[cep]'";
		}	
		if ( trim($_GET['endereco'])  != '') {	
			$auxsqlinsere .= ",endereco";
			$auxsqlvalor .= ",'$_GET[endereco]'";
		}	
		if ( trim($_GET['cidade'])  != '') {	
			$auxsqlinsere .= ",cidade";
			$auxsqlvalor .= ",'$_GET[cidade]'";
		}	
		if ( trim($_GET['uf'])  != '') {	
			$auxsqlinsere .= ",uf";
			$auxsqlvalor .= ",'$_GET[uf]'";
		}	
			
			
		$sql = "
			insert into  tpessoa (usuario,usuariodatacadastrouficha,cpfcnpj,codipessoa,nomepessoa,fone,celular,tipopessoa $auxsqlinsere)
			values('$_SESSION[usuario]','".date('d/m/Y H:i:s')."','$_GET[cpfcnpj]','$_GET[codipessoa]','$_GET[nomepessoa]','$_GET[fone]','$_GET[celular]','$_GET[tipopessoa]'  $auxsqlvalor )";		
	
		$resp = pg_exec($sql);
	
	} else if 	($_GET['sql'] == 'update') {
		
		$auxsqlinsere = '';
		$auxsqlvalor = '';
		
		
		$sql = "
			update  tpessoa
			set 
				cep = '$_GET[cep]',
				cidade = '$_GET[cidade]',
				uf = '$_GET[uf]',
				endereco = '$_GET[endereco]',
				nomepessoa = '$_GET[nomepessoa]',
				fone = '$_GET[fone]',				
				usuarioalteracao = '$_SESSION[usuario]',
				usuarioalteracaodata =  '".date('d/m/Y H:i:s')."',
				celular = '$_GET[celular]',							
				tipopessoa = '$_GET[tipopessoa]'
			where codipessoa = '$_GET[codipessoa]' ";	
			
		$resp = pg_exec($sql);
		
	}
	
	
	if 	($_GET['sqlplaca'] == 'insert') {
	
		$auxsqlinsere = '';
		$auxsqlvalor = '';
	
	
		if ( trim($_GET['anofabricacao'])  != '') {
	
			$auxsqlinsere .= ",anofabricacao";
			$auxsqlvalor .= ",'$_GET[anofabricacao]'";
		}	
	
	
	
		if ( trim($_GET['numeroseguranca'])  != '') {
	
			$auxsqlinsere .= ",numeroseguranca";
			$auxsqlvalor .= ",'$_GET[numeroseguranca]'";
			
		}
		
		//solicitado pela transcourier
		if ( trim($_GET['tara'])  != '') {
	
			$auxsqlinsere .= ",tara";
			$auxsqlvalor .= ",'$_GET[tara]'";
			
		}
		//solicitado pela transcourier
		if ( trim($_GET['capacidadecargakg'])  != '') {
	
			$auxsqlinsere .= ",capacidadecargakg";
			$auxsqlvalor .= ",'$_GET[capacidadecargakg]'";
			
		}	
		//solicitado pela transcourier
		if ( trim($_GET['capacidadecargam3'])  != '') {
	
			$auxsqlinsere .= ",capacidadecargam3";
			$auxsqlvalor .= ",'$_GET[capacidadecargam3]'";
			
		}
		//solicitado pela transcourier
		if ( trim($_GET['tipocarroceria'])  != '') {
	
			$auxsqlinsere .= ",tipocarroceria";
			$auxsqlvalor .= ",'$_GET[tipocarroceria]'";
			
		}
		if ( trim($_GET['cpfcnpjtransportador'])  != '') {
	
			$auxsqlinsere .= ",cpfcnpjtransportador";
			$auxsqlvalor .= ",'$_GET[cpfcnpjtransportador]'";
			
		}	
		if ( trim($_GET['nometransportador'])  != '') {
	
			$auxsqlinsere .= ",nometransportador";
			$auxsqlvalor .= ",'$_GET[nometransportador]'";
			
		}	
		
		/*
		tcarro.usuarioquecadastrou as cadusuario,
				tcarro.usuarioquecadastroudata as cadusuariodatacadastrouficha,
				tcarro.usuarioalteracaodata as cadusuarioalteracaodata,
				tcarro.usuarioalteracao as cadusuarioalteracao,
		*/
	
		$sqlcarro = "
			insert into  tcarro (usuarioquecadastrou,usuarioquecadastroudata,copiadoc,placa,categoria,codipessoa,marca,modelo,ufplaca,renavan,chassi,cor,antt  $auxsqlinsere)
			values('$_SESSION[usuario]','".date('d/m/Y H:i:s')."',' ','$_GET[placa]','$_GET[categoria]','$_GET[codipessoa]','$_GET[marca]','$_GET[modelo]','$_GET[ufplaca]','$_GET[renavan]','$_GET[chassi]','$_GET[cor]','$_GET[antt]'  $auxsqlvalor )";		
	
		$respplaca = pg_exec($sqlcarro);	
		
	} else if ($_GET['sqlplaca'] == 'update') {

		$auxsqlinsere = '';
		$auxsqlvalor = '';

		if ( trim($_GET['anofabricacao'])  != '') {
	
			$auxsqlinsere .= ",anofabricacao = '$_GET[anofabricacao]'";
			
		}	

		if ( trim($_GET['numeroseguranca'])  != '') {
	
			$auxsqlinsere .= ",numeroseguranca = '$_GET[numeroseguranca]'";
			
		}
			
		//solicitado pela transcourier	
		if ( trim($_GET['tara'])  != '') {
	
			$auxsqlinsere .= ",tara = '$_GET[tara]'";
			
		}	
		//solicitado pela transcourier	
		if ( trim($_GET['capacidadecargakg'])  != '') {
	
			$auxsqlinsere .= ",capacidadecargakg = '$_GET[capacidadecargakg]'";
			
		}			
		//solicitado pela transcourier	
		if ( trim($_GET['capacidadecargam3'])  != '') {
	
			$auxsqlinsere .= ",capacidadecargam3 = '$_GET[capacidadecargam3]'";
			
		}			
	
		if ( trim($_GET['cpfcnpjtransportador'])  != '') {
	
			$auxsqlinsere .= ",cpfcnpjtransportador = '$_GET[cpfcnpjtransportador]'";
			
		}
		if ( trim($_GET['nometransportador'])  != '') {
	
			$auxsqlinsere .= ",nometransportador = '$_GET[nometransportador]'";
			
		}					
		
		$sqlcarro = "
			update  tcarro
			set 
				codipessoa = '$_GET[codipessoa]',
				marca = '$_GET[marca]',
				modelo = '$_GET[modelo]',
				ufplaca = '$_GET[ufplaca]',
				chassi = '$_GET[chassi]',
				cor = '$_GET[cor]',				
				usuarioalteracao = '$_SESSION[usuario]',
				usuarioalteracaodata =  '".date('d/m/Y H:i:s')."',
				categoria = '$_GET[categoria]',				
				antt = '$_GET[antt]',
				renavan = '$_GET[renavan]'
				$auxsqlinsere
			where placa = '$_GET[placa]' ";	
						
		$respplaca = pg_exec($sqlcarro);		

	}
	
	if  ($resp && $respplaca) {	
		echo "<img src='../0bmp/pos.png'  width='20' height='20'> Dados salvos  !  ";

	}else{
		echo "<img src='../0bmp/neg.png'  width='20' height='20'> Erro gravacao !  ";
	}		 	
}  

function fmaxcodipessoa() {

  // pega um novo codi registro para tpessoa
  $sqlmaiorcodigo=pg_exec("
      select (max(codipessoa)+1) as maxcodipessoa
      from tpessoa");

  return pg_result($sqlmaiorcodigo,'maxcodipessoa');  
}  

function fmaxcodireferencia() {

  // pega um novo codi registro para tpessoa
  $sqlmaiorcodigo=pg_exec("
      select (max(codireferencia)+1) as maxcodireferencia
      from treferencia");

  return pg_result($sqlmaiorcodigo,'maxcodireferencia');  
}  

//*****************************************************************************
//*   L I B E R A C A O   P E S S O A - BUSCA PESSOA PARA GRAVAR EM TCHAMADA  *
//*   essa tela � a que o cliente acessa                                      *
//*****************************************************************************
if ( $_GET['sq']	== 'liberacaobuscapessoa' ) {
			
	if ($_GET['criteriomotorista'] == 'CPF') {
	
		$sql = 	"
			select 			
				tpessoa.codipessoa,
				cpfcnpj,
				fone,
				nomepessoa			
			from tpessoa,
				tpessoafisica
			where cpfcnpj = '$_GET[chavemotorista]' and
				tpessoa.codipessoa = tpessoafisica.codipessoa ";		
				
	//obs. tenho que ligar t pessoa com tpessoafisica senao o sistema pode fazer um inculsao
	//de proprietario pessoa fisica sem os dados preenchidos de tpessoafisica
	
	}else{
			
		$sql = 	"
			select 			
				tpessoa.codipessoa,
				cpfcnpj,
				fone,
				nomepessoa
			from tpessoa 
			where nomepessoa like '$_GET[chavemotorista]%' 
			order by nomepessoa
			limit 10 ";		
			
	}
	
	$resp = pg_exec($sql);
		
	if ( pg_numrows($resp) > 0 ){

		$exibetexto = "<table width='100%'>";
		$exibetexto .="<tr><td></td><td class='menuiz_botonoff'> CPF </td><td class='menuiz_botonoff'> Nome motorista</td><td class='menuiz_botonoff'> Fone </td><td  class='menuiz_botonoff'>Selecionar</td></tr>";
  		
        for ($i=0; $i < pg_numrows($resp ); $i++) {
 
			$arr=pg_fetch_array($resp,$i,PGSQL_ASSOC);
		  
			// esta funcao esta declarada aqui mesm neste form
			// coloquei uma funcao separada pois se colocar em join, talvez nao mostre a pessoa se nao
			// tiver vinculo
		    $arr['tipovinculo'] = verpessoavinculo($arr['codipessoa'],$_GET['contaprincipal'],$_GET['conta']);
			
			//funcao inserepsquisado( ta em formularioli
			$exibetexto .="<tr class='titulorojo'><td></td><td>$arr[cpfcnpj]</td><td>$arr[nomepessoa]</td><td>$arr[fone]</td><td class='botonoff' width='16%'><a href='#' onclick=inserepesquisado('$arr[codipessoa]','$arr[cpfcnpj]','$arr[tipovinculo]');><div id='buttonaz'><img src='../0bmp/motorista.png'  width='25' height='25'>Selecionar</div></a></td></tr>";
		}
		
		$exibetexto .="<tr><td colspan=5 class='letra_gris'> &nbsp;*Obs Limite maximo de exibicao (10) registros </td></tr>";
		
		$exibetexto .= "</table>";
		echo $exibetexto;
		
    }else{
	
		echo "<tr><td class=menuiz_botonoff><fieldset><legend><b>Mensagem </b></legend> <img src='../0bmp/neg.png'  width='45' height='35'> Registro nao encontrado ! Por favor, <a href='#' onclick=window.open('formulariomotorista.php?cpf=$_GET[chavemotorista]','mywindow','width=1000,height=700,toolbar=yes,location=yes,directories=yes,status=yes,menubar=yes,scrollbars=yes,resizable=yes');>clique aqui</a> para efetuar o cadastro </fieldset></td></tr>";	
	
	}	

//****************************
// cria negativacao black list
//****************************	
} else if ( $_GET['sq']	== 'negativar' ) {
		
	
	$codipessoa = $_GET['codipessoa'];
	$placacarro = trim(strtoupper($_GET['placacarro']));
	$placareboque = trim(strtoupper($_GET['placareboque']));
	$placasemireboque = trim(strtoupper($_GET['placasemireboque']));
	$conta = trim(strtoupper($_SESSION['conta']));
	$contaprincipal = trim(strtoupper($_SESSION['contaprincipal']));
	$motivo = trim(strtoupper($_GET['motivo']));
	
	if ($contaprincipal == '48813') {
		$conta = '887721';  //negativacao
		$contaprincipal = '887721'; //blacklist cadastro
	}
	
	$grupoprincipal = trim($arr['grupoprincipal']);
			

	
	//crio o protocolo e senha
	$t = microtime(true);
	$micro = sprintf("%06d",($t - floor($t)) * 1000000);
	$d = new DateTime( date('Y-m-d H:i:s.'.$micro, $t) );
	$senhaprotocolo = $d->format("ymdHisu"); // note at point on "u" 
		
	// para sincronizar uma data dataentrada
	$dataatual = date('d/m/Y H:i:s');	


		
				
	$msg = '';
	$auxsqlinsere = "";
	$auxsqlvalor = "";	

				
	if ($codipessoa) {	
	
		$auxsqlinsere = ",codipessoa";
		$auxsqlvalor = ",'$codipessoa'";
		
		//deleta em vinculo agregado		
		$sqldeletavinculo = "delete from tpessoavinculo where conta = $conta and codipessoa = $codipessoa ";
		$resdeletavinculo = pg_exec($sqldeletavinculo);	
	
		$sqlocorrencia = "
			insert into tocorrencia (datacriacao,obs,usuario,chavedebusca)
			values('".date('d/m/y')."','$motivo','$_SESSION[usuario]','$codipessoa')";
		
		$respocorrencia = pg_exec($sqlocorrencia);
		
		
		$s = "
			insert into tocorrenciapessoa (  			
				bloqueado,motivo,obs,usuariocriacao,datacriacao,codipessoa )
			values('t','Sinistro Suspeita','$motivo','$_SESSION[usuario]','".date('d/m/Y H:i:s')."',
				'$codipessoa' )";		
			
		$res = pg_exec($s);	
		
		
		//gravo em tchamada
						
		
		if ($_GET['placacarro'] != '') {
			$auxsqlinsere .= " ,placacarro, codipropcarro ";
			$auxsqlvalor .= ",'$_GET[placacarro]' ,'$_GET[codipropcarro]' ";	
		}	

		if ($_GET['placareboque'] != '') {
			$auxsqlinsere .= " ,placareboque, codipropreboque ";
			$auxsqlvalor .= ",'$_GET[placareboque]' ,'$_GET[codipropreboque]' ";	
		}	

		if ($_GET['placacarro'] != '') {
			$auxsqlinsere .= " ,placasemireboque, codipropsemireboque ";
			$auxsqlvalor .= ",'$_GET[placasemireboque]' ,'$_GET[codipropsemireboque]' ";	
		}	

		
		$sqlinserechamada = 	"
			insert into tchamada (liberado,custo,resposta,validade,pescon,dataentrada,datasaida,usuario,contaprincipal,conta,protocolo,statusprotocolo,senha,tipovinculo  $auxsqlinsere)
			values('f','0','$motivo','$dataatual','CON','$dataatual','$dataatual','$_SESSION[usuario]','$contaprincipal','$conta','$senhaprotocolo','10','$senhaprotocolo','' $auxsqlvalor) ";	
			
		$respinserechamada = pg_exec($sqlinserechamada);
		
		//negativacao integra com o grlog
		
		//servis grlog	
		
		// ip de contingencia 186.231.33.58		
		// original  186.225.18.161
		replicacao('CSN',$senhaprotocolo,'33042730001771','2345sfs2d15r','csn.servisgr.com.br:12126');
		
						
					
		//$msg .= faviso2("Negativacao motorista","<img src='../0bmp/pos.png' width='25' height='25'>  protocolo  ");
		$msg .=  "<table><tr><td class=menuiz_botonoff><fieldset><legend><b>Protocolo $maxprotocolo </b></legend> <img src='../0bmp/neg.png'  width='45' height='35'> Bloqueio Motorista efetuado com sucesso !  </fieldset></td></tr>";	
			
	}
		
	// negativacao veiculo
	
	if ($placacarro) {	
	
		$auxsqlinsere .= ",placacarro";
		$auxsqlvalor .= ",'$placacarro'";
	
		//deleta em vinculo agregado		
		$sqldeletavinculo = "delete from tcarrovinculo where conta = $conta and placa = '$placacarro' ";
		$resdeletavinculo = pg_exec($sqldeletavinculo);	
		
		$sqlocorrencia = "
			insert into tocorrencia (datacriacao,obs,usuario,chavedebusca)
			values('".date('d/m/y')."','$motivo','$_SESSION[usuario]','$placacarro')";
		
		$respocorrencia = pg_exec($sqlocorrencia);
		
		
		//gravo em tchamada
							
		$sqlinserechamada = 	"
			insert into tchamada (liberado,custo,resposta,validade,pescon,dataentrada,datasaida,usuario,contaprincipal,conta,protocolo,statusprotocolo,senha,tipovinculo  $auxsqlinsere)
			values('f','0','$motivo','$dataatual','CON','$dataatual','$dataatual','$_SESSION[usuario]','$contaprincipal','$conta','$senhaprotocolo','10','$senhaprotocolo','' $auxsqlvalor) ";	
			
		$respinserechamada = pg_exec($sqlinserechamada);
		
		//negativacao integra com o grlog
		
		// ip de contingencia 186.231.33.58		
		// original  186.225.18.161
		
		//servis grlog	
		replicacao('CSN',$senhaprotocolo,'33042730001771','2345sfs2d15r','csn.servisgr.com.br:12126');
		//replicacao('CSN',$senhaprotocolo,'33042730001771','2345sfs2d15r','186.225.18.161:12126');
		
					
		
		$msg .=  "<table><tr><td class=menuiz_botonoff><fieldset><legend><b>Protocolo $maxprotocolo </b></legend> <img src='../0bmp/neg.png'  width='45' height='35'> Bloqueio  veiculo $placacarro efetuado com sucesso !  </fieldset></td></tr>";	
	
		
	}
	
	if ($placareboque) {	
	
		$auxsqlinsere .= ",placareboque";
		$auxsqlvalor .= ",'$placareboque'";
		//deleta em vinculo agregado		
		$sqldeletavinculo = "delete from tcarrovinculo where conta = $conta and placa = '$placareboque' ";
		$resdeletavinculo = pg_exec($sqldeletavinculo);	
	
		$sqlocorrencia = "
			insert into tocorrencia (datacriacao,obs,usuario,chavedebusca)
			values('".date('d/m/y')."','$motivo','$_SESSION[usuario]','$placareboque')";
		
		$respocorrencia = pg_exec($sqlocorrencia);
		
		//gravo em tchamada
					
		$sqlinserechamada = 	"
			insert into tchamada (liberado,custo,resposta,validade,pescon,dataentrada,datasaida,usuario,contaprincipal,conta,protocolo,statusprotocolo,senha,tipovinculo  $auxsqlinsere)
			values('f','0','$motivo','$dataatual','CON','$dataatual','$dataatual','$_SESSION[usuario]','$contaprincipal','$conta','$senhaprotocolo','10','$senhaprotocolo','' $auxsqlvalor) ";	
			
		$respinserechamada = pg_exec($sqlinserechamada);
		
		//negativacao integra com o grlog
		
		// ip de contingencia 186.231.33.58		
		// original  186.225.18.161
		
		
		//servis grlog	
		replicacao('CSN',$senhaprotocolo,'33042730001771','2345sfs2d15r','csn.servisgr.com.br:12126');
		//replicacao('CSN',$senhaprotocolo,'33042730001771','2345sfs2d15r','186.225.18.161:12126');
		
		//cci grlog	
//		replicacao('CCI',$maxprotocolo,'10652730000473','433356ddsE','189.17.157.85:12121');
						
		
		$msg .=  "<table><tr><td class=menuiz_botonoff><fieldset><legend><b>Protocolo $maxprotocolo </b></legend> <img src='../0bmp/neg.png'  width='45' height='35'> Bloqueio  veiculo $placareboque efetuado com sucesso !  </fieldset></td></tr>";	
		
	}
	
	if ($placasemireboque) {	
	
		$auxsqlinsere .= ",placasemireboque";
		$auxsqlvalor .= ",'$placasemireboque'";
		//deleta em vinculo agregado		
		$sqldeletavinculo = "delete from tcarrovinculo where conta = $conta and placa = '$placasemireboque' ";
		$resdeletavinculo = pg_exec($sqldeletavinculo);	
		
		$sqlocorrencia = "
			insert into tocorrencia (datacriacao,obs,usuario,chavedebusca)
			values('".date('d/m/y')."','$motivo','$_SESSION[usuario]','$placasemireboque')";
		
		$respocorrencia = pg_exec($sqlocorrencia);
		
		//gravo em tchamada
					
		$sqlinserechamada = 	"
			insert into tchamada (liberado,custo,resposta,validade,pescon,dataentrada,datasaida,usuario,contaprincipal,conta,protocolo,statusprotocolo,senha,tipovinculo  $auxsqlinsere)
			values('f','0','$motivo','$dataatual','CON','$dataatual','$dataatual','$_SESSION[usuario]','$contaprincipal','$conta','$senhaprotocolo','10','$senhaprotocolo','' $auxsqlvalor) ";	
			
		$respinserechamada = pg_exec($sqlinserechamada);
		
		//negativacao integra com o grlog
		
		//servis grlog	
		replicacao('CSN',$senhaprotocolo,'33042730001771','2345sfs2d15r','csn.servisgr.com.br:12126');
		//replicacao('CSN',$senhaprotocolo,'33042730001771','2345sfs2d15r','186.225.18.161:12126');
		
		//cci grlog	
//		replicacao('CCI',$maxprotocolo,'10652730000473','433356ddsE','189.17.157.85:12121');
						
		
		$msg .=  "<table><tr><td class=menuiz_botonoff><fieldset><legend><b>Protocolo $maxprotocolo </b></legend> <img src='../0bmp/neg.png'  width='45' height='35'> Bloqueio  veiculo $placasemireboque efetuado com sucesso !  </fieldset></td></tr>";	
	
	}	
		
	
	echo $msg;
	
	
//*****************************************************************************
//*   pre liberacao, esta tela � diferente da tela do cliente, pois nao tem 
//*   contaprincipal para buscar tpessoavinculo, entao vai trazer todas as 
//*   transportadoras que o motorista carregou para facilitar a consulta
//*   esta tela � somente os funcionarios da century que acessam 
//*   historico de consultas no sistema
//*****************************************************************************
} else if ( $_GET['sq']	== 'liberacaobuscapessoa_pre' ) {
		
	$debug = '';
	$a = '';
	
	$sql = "			
		select 
			tchamada.protocolo,
			tchamada.placacarro,
			tchamada.placareboque,
			tchamada.placasemireboque,
			tchamada.placaterceiroreboque,
			tconta.nomeconta,
			tpessoa.nomepessoa,
			tchamada.usuario,
			tchamada.statusprotocolo,			
			tchamada.liberado,	
			tchamada.senha,			
			tchamada.validade,
			tchamada.pescon,
			tchamada.conta,
			tcontaprincipal.razaosocial,
			tpessoa.codipessoa,
			tpessoa.copiadoc,
			tpessoa.copiadocurl,
			tpessoa.cpfcnpj,
			tchamada.contaprincipal,
			tchamada.tipovinculo,			
			to_char(dataentrada, 'DD/MM/YY HH24:MI') as dataentrada	
		from
			tconta,			 
			tcontaprincipal,
			tchamada LEFT OUTER JOIN tpessoa ON (tchamada.codipessoa = tpessoa.codipessoa)
		where tchamada.conta = tconta.conta and 
			pescon <> 'CAN'		and
			tchamada.contaprincipal = tcontaprincipal.contaprincipal ";
		
	
	// se nao for funcionario century, 
	// permite buscar as liberacoes de todas as filiais ( serivs pediu para ver as liberacoes das contas)
	// e autoriza somente o supervisor da conta a acessar outras filiais senao mostra so os da filial
	if ($_SESSION['contaprincipal'] != '48813') {
	
		if ($_SESSION['nivelativo'] < 29 ) {
			$sql .= "	and	tchamada.contaprincipal = '$_SESSION[contaprincipal]'  ";
		}else{	
			$sql .= "	and	tchamada.conta = '$_SESSION[conta]'  ";
		}	
		
	}
	
	if ($_GET['criteriomotorista'] == 'CPF') {
	
		$sql .= "	and	tpessoa.cpfcnpj = '$_GET[chavemotorista]'  
			order by tchamada.dataentrada desc ";
					
	}else if ($_GET['criteriomotorista'] == 'NOMEMOTORISTA') {
		
		$sql .= " and tpessoa.nomepessoa like '$_GET[chavemotorista]'  
			order by tchamada.dataentrada desc ";	

	}else if ($_GET['criteriomotorista'] == 'RG') {
		
		$sql = "			
			select 
				tchamada.protocolo,
				tchamada.placacarro,
				tchamada.placareboque,
				tchamada.placasemireboque,
				tchamada.placaterceiroreboque,
				tconta.nomeconta,
				tpessoa.nomepessoa,
				tchamada.usuario,
				tchamada.statusprotocolo,			
				tchamada.liberado,	
				tchamada.senha,			
				tchamada.validade,
				tchamada.pescon,
				tchamada.conta,
				tcontaprincipal.razaosocial,
				tpessoa.codipessoa,
				tpessoa.copiadoc,
				tpessoa.copiadocurl,
				tpessoa.cpfcnpj,
				tchamada.contaprincipal,
				tchamada.tipovinculo,			
				to_char(dataentrada, 'DD/MM/YY HH24:MI') as dataentrada	
			from
				tconta,			 
				tcontaprincipal,
				tpessoafisica,
				tchamada LEFT OUTER JOIN tpessoa ON (tchamada.codipessoa = tpessoa.codipessoa)
			where tchamada.conta = tconta.conta and 
				pescon <> 'CAN'		and
				tchamada.contaprincipal = tcontaprincipal.contaprincipal and
				tpessoa.codipessoa = tpessoafisica.codipessoa and
				tpessoafisica.rg like '$_GET[chavemotorista]'  
					order by tchamada.dataentrada desc
				";	
			
	}

	$debug = $sql;
	
	$res = pg_exec($sql);
	
	if ( pg_numrows($res) > 0 ){
		
	
		$a = "<table class='tabla_listado' width='100%'>";
		$a .= "<tr class='letra_gris'>
				<td class=menuiz_botonoff> Sit <br> Data</td>
				
				<td class=menuiz_botonoff> Conta Principal / Conta </td>
				<td class=menuiz_botonoff> Vinculo / Nome / Placa</td>
				<td class=menuiz_botonoff> Senha </td>
				<td class=menuiz_botonoff> Validade </td>
				<td class=menuiz_botonoff> Resp </td>
				<td class=menuiz_botonoff> Cons </td>
				<td class=menuiz_botonoff> Ficha </td></tr>";
		
		for ($i=0; $i < pg_numrows($res ); $i++) {
 
			$arr=pg_fetch_array($res,$i,PGSQL_ASSOC);
			
			
					
				
			if ( $arr['liberado'] == 't' ) {
			
				
				$a .= "<tr class='fila_subtotal'>
							<td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='20' height='20'> $arr[pescon]<br>  $arr[dataentrada]</td>
							<td>$arr[razaosocial] <br>$arr[nomeconta]</td>							
							<td> $arr[tipovinculo] <b>$arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] $arr[placaterceiroreboque] </td>
							<td>$arr[senha] </td>
							<td>$arr[validade]</td>";
							
			}else  { //if ( $arr['liberado'] == 'f' ){
				$a .= "<tr class='fila_subtotal'>
							<td> <img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='20' height='20'> $arr[pescon]<br> $arr[dataentrada]</td>
							<td>$arr[razaosocial] <br>$arr[nomeconta]</td>							
							<td> $arr[tipovinculo] <b>$arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] $arr[placaterceiroreboque] </td>
							<td align=center> --- </td>
							<td align=center> --- </td> 
				";			
			}
			
			// declaracao de inserepesquisado(  /formulariopreliberacao
			// declaracao de inserepesquisado(  /negativar
			
			$a .= "
				<td><a href='#ancora_resposta' onclick=\"mostraresposta('$arr[senha]');\" ><div id='buttonaz'><img src='../0bmp/pasta.png' align='absmiddle' border='0' width='15' height='15'>Resposta</div></a></td>
				<td><a href='#' onclick=inserepesquisado('$arr[codipessoa]','$arr[cpfcnpj]','$arr[tipovinculo]','$arr[contaprincipal]','$arr[conta]','$arr[placacarro]','$arr[placareboque]','$arr[placasemireboque]','$arr[placaterceiroreboque]');><div id='buttonaz'><img src='../0bmp/libe.png' align='absmiddle' border='0' width='15' height='15'>Consultar</div></a></td>
				<td><a href='#' onclick=mostraficha('$arr[codipessoa]','$arr[contaprincipal]','$arr[placacarro]','$arr[placareboque]','$arr[placasemireboque]');><div id='buttonaz'><img src='../0bmp/banco.png' align='absmiddle' border='0' width='15' height='15'>Ficha</div></a></td>
				<td><a href='#' onclick=negativar('','$arr[codipessoa]','$arr[contaprincipal]','$arr[conta]','$arr[placacarro]','$arr[placareboque]','$arr[placasemireboque]');><div id='buttonaz'><img src='../0bmp/neg.png' align='absmiddle' border='0' width='15' height='15'>Negativar</div></a></td>
							
<td><a href='#' onclick=emailrespostaconsulta('$arr[protocolo]')   ><div id='buttonaz'> Email </div></a></td>								
							
			";
			
		}	
				
		$a .= "</table>";	
				
        $a .="<a name='ancora_resposta' id='ancora_resposta'></a>";		
		
	}else{
	
		$a .= faviso2("Este registro ainda nao possui uma liberacao cadastral","para uma nova liberacao <a href='formularioliberacao.php'> <img src='../0bmp/libe.png' width='25' height='25'> clique aqui </a>");

	}

	$a .= ftela('tela12sdr5');
	
	//echo "<br> $debug <br>"; 	
	echo $a;
	
} else if ( $_GET['sq']	== 'liberacaobuscaplaca_pre' ) {
		
	$debug = '';
	$a = '';
	
	$sql = "			
		select 
			tchamada.protocolo,
			tchamada.placacarro,
			tchamada.placareboque,
			tchamada.placasemireboque,
			tchamada.placaterceiroreboque,
			tconta.nomeconta,
			tpessoa.nomepessoa,
			tchamada.usuario,
			tchamada.statusprotocolo,			
			tchamada.liberado,	
			tchamada.senha,			
			tchamada.validade,
			tchamada.pescon,
			tchamada.conta,
			tcontaprincipal.razaosocial,
			tchamada.tipovinculo,
			tpessoa.codipessoa,
			tpessoa.cpfcnpj,
			tchamada.contaprincipal,			
			to_char(dataentrada, 'DD/MM/YY HH24:MI') as dataentrada	
		from
			tconta,			 
			tcontaprincipal,
			tchamada LEFT OUTER JOIN tpessoa ON (tchamada.codipessoa = tpessoa.codipessoa)
		where tchamada.conta = tconta.conta and 					
			tchamada.contaprincipal = tcontaprincipal.contaprincipal ";				
	
	$sql .= "	and	tchamada.$_GET[categoriaveiculo] = '$_GET[chaveplaca]'  
			order by tchamada.validade desc,tchamada.dataentrada desc ";					
				


	$debug = $sql;
	
	$res = pg_exec($sql);
	
	if ( pg_numrows($res) > 0 ){
	
		$a = "<table class='tabla_listado' width='100%'>";
		$a .= "<tr class='letra_gris'>
				<td class=menuiz_botonoff> Sit </td>
				<td class=menuiz_botonoff> Data </td>
				<td class=menuiz_botonoff> Conta Principal / Conta </td>
				<td class=menuiz_botonoff> Vinculo / Nome / Placa</td>
				<td class=menuiz_botonoff> Senha </td>
				<td class=menuiz_botonoff> Val Pesq </td>
				<td class=menuiz_botonoff> Resp </td>
				<td class=menuiz_botonoff> Cons </td>
				<td class=menuiz_botonoff> Ficha </td></tr>";
		
		for ($i=0; $i < pg_numrows($res ); $i++) {
 
			$arr=pg_fetch_array($res,$i,PGSQL_ASSOC);
			
			//$arr['tipovinculo']  = verpessoavinculo($arr['codipessoa'],$arr['contaprincipal']);
						
				
			// funcao inserepesquisado(  esta decladara em formulariopreliberacao
			// negativar ta no formulario pre liberacao	
			
			if ( $arr['liberado'] == 't' && $arr['pescon'] <> 'CAN') {
				$a .= "<tr class='fila_subtotal'>
							<td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'>$arr[pescon]</td>
							<td> $arr[dataentrada]</td>
							<td>$arr[razaosocial] <br>$arr[nomeconta]</td>							
							<td> $arr[tipovinculo] $arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] </td>
							<td>$arr[senha] </td>
							<td>$arr[validade]</td>
							<td  class='botonoff'><a href='#ancora_resposta' onclick=\"mostraresposta('$arr[senha]');\" ><div id='buttonaz'><img src='../0bmp/pasta.png' align='absmiddle' border='0' width='20' height='20'>Resposta</div></a></td>
							<td  class='botonoff'><a href='#' onclick=inserepesquisado('$arr[codipessoa]','$arr[cpfcnpj]','$arr[tipovinculo]','$arr[contaprincipal]','$arr[conta]','$arr[placacarro]','$arr[placareboque]','$arr[placasemireboque]','$arr[placaterceiroreboque]');><div id='buttonaz'><img src='../0bmp/libe.png' align='absmiddle' border='0' width='20' height='20'>Consultar</div></a></td>
							<td  class='botonoff'><a href='#' onclick=mostraficha('$arr[codipessoa]','$arr[contaprincipal]','$arr[placacarro]','$arr[placareboque]','$arr[placasemireboque]');><div id='buttonaz'><img src='../0bmp/banco.png' align='absmiddle' border='0' width='20' height='20'>Ficha</div></a></td>
							<td><a href='#' onclick=negativar('$arr[nomepessoa]','','$arr[contaprincipal]','$arr[conta]','$arr[placacarro]','$arr[placareboque]','$arr[placasemireboque]');><div id='buttonaz'><img src='../0bmp/neg.png' align='absmiddle' border='0' width='15' height='15'>negativar</div></a></td></tr>";
							
			}else if ( $arr['liberado'] == 'f' && $arr['pescon'] <> 'CAN'){
				$a .= "<tr class='fila_subtotal'>
							<td> <img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'>$arr[pescon]</td>
							<td> $arr[dataentrada]</td>
							<td>$arr[razaosocial] <br>$arr[nomeconta]</td>							
							<td> $arr[tipovinculo] $arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] </td>
							<td align=center> --- </td>
							<td align=center> --- </td> 
							<td  class='botonoff'><a href='#ancora_resposta' onclick=\"mostraresposta('$arr[senha]');\" ><div id='buttonaz'><img src='../0bmp/pasta.png' align='absmiddle' border='0' width='20' height='20'>Resposta</div></a></td>
							<td  class='botonoff'><a href='#' onclick=inserepesquisado('$arr[codipessoa]','$arr[cpfcnpj]','$arr[tipovinculo]','$arr[contaprincipal]','$arr[conta]','$arr[placacarro]','$arr[placareboque]','$arr[placasemireboque]','$arr[placaterceiroreboque]');><div id='buttonaz'><img src='../0bmp/libe.png' align='absmiddle' border='0' width='20' height='20'>Consultar</div></a></td>
							<td  class='botonoff'><a href='#' onclick=mostraficha('$arr[codipessoa]','$arr[contaprincipal]','$arr[placacarro]','$arr[placareboque]','$arr[placasemireboque]');><div id='buttonaz'><img src='../0bmp/banco.png' align='absmiddle' border='0' width='20' height='20'>Ficha</div></a></td>
							<td><a href='#' onclick=negativar('$arr[nomepessoa]','','$arr[contaprincipal]','$arr[conta]','$arr[placacarro]','$arr[placareboque]','$arr[placasemireboque]');><div id='buttonaz'><img src='../0bmp/neg.png' align='absmiddle' border='0' width='15' height='15'>negativar</div></a></td></tr>";
			}			
		}	
				
		$a .= "</table>";	
        $a .="<a name='ancora_resposta' id='ancora_resposta'></a>";		
		
	}else{
	
		$a .= faviso2("Este registro ainda nao possui uma liberacao cadastral","para uma nova liberacao <a href='formularioliberacao.php'> <img src='../0bmp/libe.png' width='25' height='25'> clique aqui </a>");

	}

	$a .= ftela('0interrisco_funajax.liberacaobuscaplaca_pre');
	

	//echo "<br> $debug <br>"; 	
	echo $a;

	
	

	
//**********************************
//*   L I B E R A C A O   carro  *
//**********************************	
} else if ( $_GET['sq']	== 'preparacestaenviopesquisa' ) {

	$placaounome = $_GET[placaounome];
	$tipodebusca = $_GET[tipodebusca];
	$tipoveiculo = $_GET[tipoveiculo];

	if ($tipodebusca == 'PLACA') {
	
		$sql = 	"
			select 			
				placa,
				nomepessoa,
				marca,
				modelo,
				tpessoa.codipessoa
			from tcarro,
				tpessoa
			where placa = '$placaounome' and
				tcarro.codipessoa = tpessoa.codipessoa ";		
			
		
	}else{
	
		$sql = 	"
			select 			
				placa,
				nomepessoa,
				marca,
				modelo,
				tpessoa.codipessoa
			from tcarro,
				tpessoa
			where tpessoa.nomepessoa like 'placaounome%' and
				tcarro.codipessoa = tpessoa.codipessoa 
				order by placa
				limit 10 ";		
	
	}
	
	$resp = pg_exec($sql);
	
	if ( pg_numrows($resp) > 0 ){
  
		$tela = "<table width='100%'>";
		$tela .="<tr><td></td><td class='menuiz_botonoff'> Placa </td><td class='menuiz_botonoff'> Nome proprietario carro</td><td class='menuiz_botonoff'> Marca </td><td class='menuiz_botonoff'> Modelo </td><td class='menuiz_botonoff'> Selecinar </td></tr>";
  		
        for ($i=0; $i < pg_numrows($resp ); $i++) {
 
			$arr=pg_fetch_array($resp,$i,PGSQL_ASSOC);
		  
			// funcao inserecesta( ta declarada em formularioliberacao
		  
			$tela .="<tr  class='titulorojo'>
								<td></td>
								<td>$arr[placa]</td>
								<td>$arr[nomepessoa]</td>
								<td>$arr[marca]</td>
								<td>$arr[modelo]</td>
								<td class='botonoff'  width='16%'><a href='#' onclick=\"inserecesta('$arr[placa]','$arr[codipessoa]','$tipoveiculo');\"><div id='buttonaz'><img src='../0bmp/carro2.png'  width='25' height='25'>Selecionar $tipoveiculo</div></a></td></tr>
							<tr><td colspan=5> </td></tr>		
							<tr><td colspan=5 class='letra_gris'> &nbsp;*Obs Favor confirmar proprietario do carro, se divergente atualilize <a href='#' onclick=window.open('formulariocarro.php?placa=$_GET[chavecarro]','mywindow','width=1000,height=700,toolbar=yes,location=yes,directories=yes,status=yes,menubar=yes,scrollbars=yes,resizable=yes');>clicando aqui</a> </td></tr>
			";

		}
		$tela .="<tr><td colspan=5 class='letra_gris'> &nbsp;*Obs Limite maximo de exibicao (10) registros </td></tr>";
		
		$tela .= "</table>";
		echo $tela;
		
    }else{
	
		echo "<tr><td class=menuiz_botonoff><fieldset><legend><b>Mensagem </b></legend> <img src='../0bmp/neg.png'  width='45' height='35'> Registro nao encontrado ! Por favor, <a href='#' onclick=window.open('formulario$tipoveiculo.php?placa=$placaounome','mywindow','width=1000,height=700,toolbar=yes,location=yes,directories=yes,status=yes,menubar=yes,scrollbars=yes,resizable=yes');>clique aqui</a> para efetuar o cadastro do $tipoveiculo</fieldset></td></tr>";	
	
	}	
			
			//************************************************************************ //
			//************************************************************************ //
			//************************************************************************ //
			//************************************************************************ //
			//************************************************************************ //
			//*                                                                      * //
			//*                                                                      * //
			//*                                                                      * //
			//*                                                                      * //
			//* 		C R I A   U M A    N O V A     C H A M A D A                 * //
			//*                                                                      * //
			//*                                                                      * //
			//*                                                                      * //
			//*                                                                      * //
			//*    DEFINE SE � UMA CONSULTA OU PESQUISA E DEFINE VALOR DA PESQUISA   * //
			//*                                                                      * //
			//*                                                                      * //
			//*                                                                      * //
			//*                                                                      * //
			//*                                                                      * //
			//*                                                                      * //  
			//************************************************************************ //
			//************************************************************************ //
			//************************************************************************ //
			//************************************************************************ //
			//************************************************************************ //


} else if ( $_GET['sq']	== 'gravanovachamada' ) {
	
	//nova versao da chamada
	//declarado em /-/interrisco/gravanovachamada_sql
 echo novachamada_v2($_GET['contaprincipal'],
	$_GET['conta'],
	$_GET['tipovinculo'],
	$_GET['codipessoa'],
	$_GET['placacarro'],
	$_GET['placareboque'],
	$_GET['placasemireboque'],
	$_GET['placaterceiroreboque'],
	$_GET['codipropcarro'],
	$_GET['codipropreboque'],
	$_GET['codipropsemireboque'],
	$_GET['codipropterceiroreboque'],
	'f',
	'',
	$_GET['pesquisaboeconvencional']);
 	
	echo ftela('0interrisco_funajax.gravanovachamada*');
	

} else if ( $_GET['sq']	== 'alteratiporastreamento' ) {
	
	//****************
	// insere o tiporastreamento servis csn
	//****************
			
	$sqlbusca = "
		Select  protocolo 
		from tchamada,tpessoa
		where tchamada.codipessoa = tpessoa.codipessoa and
			tpessoa.cpfcnpj = '$_GET[cpfcnpj]'
			order by dataentrada desc	";		
		
	$resbusca = pg_exec($sqlbusca);
		
	
	if ( pg_numrows($resbusca) > 0 ){
		
		$protocolo =  pg_result($resbusca,'protocolo');	
				
	
		$sqlbusca = "select protocolo from tiporastreamento where  protocolo = $protocolo ";
		
		$sqlbuscatipo = pg_exec($sqlbusca);
	
		
		if ( pg_numrows($sqlbuscatipo) > 0  ){
		
			$sqltiporastreamento = "
			
				update tiporastreamento
				set tiporastreamento = '$_GET[tiporastreamento]'
				where protocolo = $protocolo ";
				
			$respsqltiporastreamento = pg_exec($sqltiporastreamento);
			
		}	else {
		
			$sqltiporastreamento = "
					
					insert into tiporastreamento(protocolo,tiporastreamento ) 
					values ('$protocolo','$_GET[tiporastreamento]');
						
				";
					
			$respsqltiporastreamento = pg_exec($sqltiporastreamento);
			
		}	
			
		if ( $respsqltiporastreamento ) {
			
			//echo "alterado $sqltiporastreamento $sqlbusca";
			echo "<BR><table align=center><tr><td class=menuiz_botonoff align=center><fieldset><legend><b></b></legend>  <img src='../0bmp/pos.png'  width='45' height='35'>  <h3> Salvo com sucesso</h3></fieldset></td></tr></table>";
			
		} else {
			
			echo "<BR><table align=center><tr><td class=menuiz_botonoff align=center><fieldset><legend><b></b></legend> <img src='../0bmp/interrogacao2.png'  width='45' height='35'>  <h3>Nao foi possivel efetuar a gravacao</h3></fieldset></td></tr></table>";
				
		}						
	
	}	
	
} else if ( $_GET['sq']	== 'registrospendentes' ) {

    // objetivo tela que mostra todos os registros pendentes e em pesquisa, e o status de cada consulta
	// este selec traz todos os registros que ainda n�o foi feito resposta
	// mostra todos validade = null, o select tb calcula o tempo que a ficha ficou parada
	
	$enviarconsultoria = '';
		
		
	//$_SESSION['filtroconta']  = $_GET['filtroconta'];		
		
	//mostra as consultas serasa para fazer
	
	$sql ="
		select 						
			to_char(dataentrada, 'DD/MM HH24:MI') as dataentrada,
			to_char((current_timestamp - dataentrada), 'HH24:MI') as tempo,
			tconta.nomeconta,
			tconta.nextel as nextelconta,
			tconta.fone as foneconta,
			tcontaprincipal.razaosocial,
			tserasa.cpfcnpj,
			tserasa.statusprotocolo,			
			tserasa.usuarioqueinseriu,
			tserasa.protocolo,
			tserasa.uf,
			tserasa.tipocpfcnpj,
			tserasa.historico,
			tserasa.resposta,
			usuarioenviocivil,
			datenviocivil,
			tconta.email,
			tpessoa.codipessoa,
			tpessoa.nomepessoa,
			tpessoafisica.rg,
			tpessoafisica.dtnascimento,
			tpessoa.copiadoc,
			tpessoa.copiadocurl,
			tpessoafisica.ufrg,
			tpessoafisica.nomemae
			
		from tserasa LEFT OUTER JOIN tpessoafisica ON (tserasa.codipessoa = tpessoafisica.codipessoa),			
			tconta,
			tpessoa,
			tcontaprincipal
		where tserasa.conta = tconta.conta and
			tconta.contaprincipal = tcontaprincipal.contaprincipal and
			tserasa.cpfcnpj = tpessoa.cpfcnpj and
			((tserasa.tipo = 'RDO') or (tserasa.tipo = 'COMPLETA') or (tserasa.tipo = 'TJ')) AND
			tserasa.statusprotocolo < 10 ";	
			
	// se nao for  contaprincipal century mostra s� a ficha
	if ($_SESSION['contaprincipal'] != 48813) {
		$sql .= " and tserasa.conta = $_SESSION[conta] ";
	}

	
	$sql .= " order by tserasa.cpfcnpj, tserasa.dataentrada ";
	
	$res = pg_exec($sql);	
		
	if ( pg_numrows($res) > 0 ){
	
		//cabe�alho relatorio rdo	
	

		$tl = "<table class='table table-dark table-striped table-bordered table-condensed table-hover'>";
		$tl .= "<thead>";
		$tl .= "<tr><th scope='col' colspan=7>Registros em RDO (". pg_num_rows($res).") usuario $_SESSION[usuario] </th></tr>";
		$tl .= "<tr><th scope='col'>Data</th><th scope='col'>Conta</th><th scope='col'>Pesquisado</th><th scope='col'>Produto</th><th scope='col'>Time</th><th scope='col'>Usuario</th><th scope='col'></th></tr>";
		$tl .= "</thead>";
		$tl .= "<tbody tbody-striped>";
	
 		for ($i=0; $i < pg_numrows($res ); $i++) {
			
			$arrserasa = pg_fetch_array($res,$i,PGSQL_ASSOC);

			$statusrdo = '';
			if ( $arrserasa['usuarioenviocivil'] != '') 
				$statusrdo = "<s1>Enviado consultoria em $arrserasa[datenviocivil] de $arrserasa[usuarioenviocivil]</s1>";
			else
				$statusrdo = "$arrserasa[usuarioqueinseriu] <fontered></fontered>";
					
			$tl .= "<tr><TD>$arrserasa[dataentrada]</td>
				<td>$arrserasa[razaosocial] <b>$arrserasa[nomeconta]</td>
				<td>CPF: $arrserasa[cpfcnpj] $arrserasa[nomepessoa]</td>
				<td>$arrserasa[historico] </td>
				
			";

/*
			if ($_SESSION['nivelativo'] > 30) 	
				   $tl .= "<td><a href='#' onclick=\"window.open('deletaserasa.php?&protocolo=$arrserasa[protocolo]','','width=300,height=300');\"  >$arrserasa[tempo] Del</a> </td>";
			    else
					$tl .= "<td>$arrserasa[tempo]</td>";
			

			if ($_SESSION['contaprincipal'] == 48813) {

				
				//$tl .= "<td><img src='../0bmp/$arrserasa[liberado]a.png' align='absmiddle' border='0' width='25' height='25'>  </td>";
				$tl .= "<td>  <a href='#' onclick=\"window.open('../0ir/rdoresposta.php?&tipoconsulta=Consulta RDO - Estadual&protocolo=$arrserasa[protocolo]&cpfcnpj=$arrserasa[cpfcnpj]&nomepessoa=$arrserasa[nomepessoa]&email=$arrserasa[email]&resposta=$arrserasa[resposta]&historico=$arrserasa[historico]','','width=1200,height=600');\"  > <div id='buttonaz'><img src='../0bmp/engre.png' width='25' height='25'  border='0' align='absmiddle'></div>  </a></td>";
				
			
			}	*/

			if ($_SESSION['nivelativo'] > 30) 	
				   $tl .= "<td><a href='#rdo$i' onclick=\"window.open('deletaserasa.php?&protocolo=$arrserasa[protocolo]','','width=300,height=300');\"  >$arrserasa[tempo] Del</a> </td>";
			    else
					$tl .= "<td>$arrserasa[tempo]</td>";
			

			if ($_SESSION['contaprincipal'] == 48813) {

				
				//$tl .= "<td><img src='../0bmp/$arrserasa[liberado]a.png' align='absmiddle' border='0' width='25' height='25'>  </td>";
				$tl .= "<td>  <a href='#rdo$i' onclick=\"window.open('../0ir/rdoresposta.php?&tipoconsulta=Consulta RDO - Estadual&protocolo=$arrserasa[protocolo]&cpfcnpj=$arrserasa[cpfcnpj]&nomepessoa=$arrserasa[nomepessoa]&email=$arrserasa[email]&resposta=$arrserasa[resposta]&nomeconta=$arrserasa[nomeconta]&uf=$arrserasa[uf]&historico=$arrserasa[historico]&rg=$arrserasa[rg]&ufrg=$arrserasa[ufrg]&dtnascimento=$arrserasa[dtnascimento]&nomemae=$arrserasa[nomemae]','','width=1200,height=600');\"  > <div id='buttonaz'><img src='../0bmp/engre.png' width='25' height='25'  border='0' align='absmiddle'></div>  </a></td>";
								
			}	
			
		}		
		
	}
	
	//pega os dados pre gravados para o usuario

  	$sql = "		
		select 
			tchamada.protocolo,
			tchamada.placacarro,
			tchamada.placareboque,
			tchamada.placasemireboque,
			tchamada.placaterceiroreboque,
			tconta.nomeconta,
			tconta.grupo,
			tchamada.usuario,
			tchamada.statusprotocolo,			
			tchamada.codipropcarro,
			tchamada.codipropreboque,
			tchamada.codipropsemireboque,			
			tchamada.codipropterceiroreboque,	
			tchamada.liberado,			
			tchamada.conta,
			tchamada.contaprincipal,
			tchamada.tipovinculo,
			tchamada.pescon,
			tchamada.codipessoa,
			
			tchamada.auxlibcarro,
			tchamada.auxlibreboque,
			tchamada.auxlibsemireboque,
			tchamada.auxlibterceiroreboque,
			
			to_char(tchamada.auxdatareboque,'DD/MM/YY') as auxdatareboque,
			to_char(tchamada.auxdatasemireboque,'DD/MM/YY') as auxdatasemireboque ,
			to_char(tchamada.auxdataterceiroreboque,'DD/MM/YY') as auxdataterceiroreboque,
			tconta.fone as foneconta,
			tconta.nextel as nextelconta,
			tchamada.pesquisador,			
			tpessoa.nomepessoa,
			tpessoa.uf,		
			tpessoa.cpfcnpj,
			tpessoa.cidade,
			tpessoa.endereco,		
			tpessoa.fone,
			tpessoa.celular,
			tpessoa.copiadoc,
			tpessoa.copiadocurl,
			tcontaprincipal.razaosocial,			
			to_char(dataentrada, 'DD/MM HH24:MI') as dataentrada,
			to_char((current_timestamp - dataentrada), 'HH24:MI') as tempo			
		from
			tconta,
			tcontaprincipal,
			tchamada LEFT OUTER JOIN tpessoa ON (tchamada.codipessoa = tpessoa.codipessoa)
		where datasaida is null and		
			tchamada.conta = tconta.conta and
			tconta.contaprincipal = tcontaprincipal.contaprincipal 
			";
			
		//	and
		//	tconta.conta != 2101042222 ";  //excluo por enquanto as fichas da vb
	


	
	if ( trim($_GET['campofiltro']) == 'igual'){
	
		$sql .= " and tcontaprincipal.razaosocial  like '$_GET[campofiltro]'   ";
	
		
	}else if ( trim($_GET['campofiltro']) == 'diferente'){
	
		$sql .= " and tcontaprincipal.razaosocial  not like '$_GET[campofiltro]'   ";
	
		
	}	
	

			
	// aqui eu verifico se o usuario nao e century,
	// para que o cliente nao veja todos os registros em pesquisa
	if ($_SESSION['contaprincipal'] != '48813') {
				
				
	
					
		// se tiver grupo principal, visualza os grupos menores
		if ( ($_SESSION['grupoprincipal']) != '' and $_SESSION['ativogrupoprincipal'] == 't') {		
				
			$sql .= " and tconta.grupoprincipal = '$_SESSION[grupoprincipal]'	";	
				
		// se mao tiver grupo entao pega somente a filial
		} else if ( ($_SESSION['grupo']) != '' and $_SESSION['nivelativo'] >= 25 ) {
			
			$sql .= " and tconta.grupo = '$_SESSION[grupo]'	";
			
		} else {
		   
			$sql .= " and tchamada.conta = $_SESSION[conta]	";
			
		   // se grupo foi cadastrado entao pega as liberacoes de todas as filiais
		   // isso foi feito para gerenciadora verificar 
		}		
	}else{
	
	
	}	
    
  //  if ( $_GET[classificacao] != '') {
	//	$sql .= "	order by $_GET[classificacao] ";
        
   // }else{
   //     $sql .= "	order by protocolo  ";
    //}    
  
	//coloco as consultas antes
    $sql .= "	order by pescon,protocolo  ";
  
  
  
//echo $sql;  
//echo "<br>$sql<br>";

	$res = pg_exec($sql);
		
	$a = "<table class='table table-dark table-striped table-bordered table-condensed table-hover'>";
	$a .= "<thead>";
	$a .= "<tr><td>* ".pg_numrows($res)." <a href='#' onclick=salvalayoutazregistrospendentes('dataentrada')> Data </a> </td><td > <a href='#' onclick=salvalayoutazregistrospendentes('contaprincipal,conta,dataentrada')> Conta  </a>  </td><td>Motorista</td><td >Veiculo</td><td >Reb</td><td>Semireb</td><td >3reb</td><td > <a href='#' onclick=salvalayoutazregistrospendentes('nomepessoa')> Pesquisa </a> </td> <td > Percentual </td><td > H </td></tr>";
	$a .= "</thead>";
	$a .= "<tbody tbody-striped>";
	
	if ( pg_numrows($res) > 0 ){
  	
		// se for usuario century permite o link para fazer as pesquisas
		// contaprincipal � o logado century		
			
		//$a .= "<tr class='letra_gris'><td class=menuiz_botonoff COLSPAN=9> </TD> </tr>";
		
		if ($_SESSION['contaprincipal'] == '48813') {

			for ($i=0; $i < pg_numrows($res ); $i++) {
			
				$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
							
				$arr['grupo'] = trim($arr['grupo'] );
				$arr['grupo'] = str_replace(' ', '',$arr['grupo']);
				$arr['grupo'] = str_replace(' ', '',$arr['grupo']);
				$arr['grupo'] = str_replace(' ', '',$arr['grupo']);
				$arr['grupo'] = str_replace(' ', '',$arr['grupo']);
				
				$corren = '';
				
				//****************************
				//* pega os arquivos de upload arquivo 
				//****************************
				
				
				//upload tela
				
				
				//**************************************** 221020
				//verifico se precisa fazer o consultoria				
				//****************************************
				
				if ( $arr['codipessoa'] != '') {
					$enviarconsultoria = botaoconsultoria($arr['codipessoa'],$arr['tipovinculo'],$arr['contaprincipal'],$arr['conta']) ;	
				}	

				if ($arr['statusprotocolo'] > 10) {
					$arr['statusprotocolo'] = 10;
				}
				
				if ($arr['pescon'] == 'REN')
					$corren = " BGCOLOR= '#F2F2F2'";
				
				if ($arr['pescon'] == 'PES')
					$corren = " BGCOLOR= '#F0F8FF'";

				if ($arr['pescon'] == 'CON')
					$corren = " BGCOLOR= '#F8F8F8'";

				
				//autoriza para deletar ficha
				if ( $_SESSION['nivelativo'] >= 29) {
					$deletaficha = "<a href='#' onclick=\"baixaficha('$arr[protocolo]')\">$arr[protocolo]</a>";
				}else{	
					$deletaficha = "$arr[protocolo]";
				}	
					
					
					
				//BUSCO SE TA LIBERADO OU NAO.	
					
			
				
				$arr['auxlibcarro'] = "<img src='../0bmp/$arr[auxlibcarro].png' width='15' height='15'  align='absmiddle'>";	
				$arr['auxlibreboque'] = "<img src='../0bmp/$arr[auxlibreboque].png' width='15' height='15'  align='absmiddle'>";	
				$arr['auxlibsemireboque'] = "<img src='../0bmp/$arr[auxlibsemireboque].png' width='15' height='15'  align='absmiddle'>";	
				$arr['auxlibterceiroreboque'] = "<img src='../0bmp/$arr[auxlibterceiroreboque].png' width='15' height='15'  align='absmiddle'>";	
				
				$hoje = date('d/m/y');

				
				
				if ( $arr['auxdatareboque'] == $hoje ) 
					$arr['auxdatareboque'] = 'Hoje';
				
				if ( $arr['auxdatasemireboque'] == $hoje ) 
					$arr['auxdatasemireboque'] = 'Hoje';
			
				if ( $arr['auxdataterceiroreboque'] == $hoje ) 
					$arr['auxdataterceiroreboque'] = 'Hoje';
						
				// coloca vermelho em registro novo								
				if ($arr['pesquisador'] == '' ) {
				
				
					//A FUNCAO PESQUISA ( TA EM  registrospendentes.php
				
					// novo registro mostra a consulta civil
					$a .= "<tr>
						
						<td>".substr($arr['dataentrada'],6,5)."  $arr[pescon] <br> $arr[usuario]  </td>
						<td>".substr($arr['razaosocial'],0,17)." <br> <b>".substr($arr['nomeconta'],0,30)."</b></td>
						<td>  $arr[cpfcnpj] $arr[nomepessoa] </td>
						<td>  $arr[placacarro]</td>
						<td>  $arr[placareboque]</td>
						<td>  $arr[placasemireboque] </td>
						<td>  $arr[placaterceiroreboque]</td>
												
						<td> <font color='#800000'> $arr[tempo] Novo $deletaficha </td>
						<td><a href='#' onclick='pesquisador($arr[protocolo])'; ><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'> </a> </td>
					
						<td><a href='#' class='btn btn-danger btn-sm mb-2' onclick=onclick=pesquisa('$arr[cpfcnpj]','$arr[grupo]','$arr[protocolo]','$arr[codipessoa]','$arr[placacarro]','$arr[placareboque]','$arr[placasemireboque]','$arr[placaterceiroreboque]','$arr[conta]','$arr[contaprincipal]','consulta','$arr[enviaemailresposta]')>  Novo </a></td>

						</tr>";					
						
					// funcao pesquisador() esta em /0interrisco/registrospendentes.php	
					// funcao pesquisa () esta em /0interrisco/registrospendentes.php	
					// funcao uploaddocumentos() est� na pasta /funcoes/telauploadmotoristaveiculo.js e incluido em /0interrisco/registrospendentes.php



									
				}else{
						
					//$arr[tipovinculo]
						
					
					$a .= "<tr  >
					
						<td>".substr($arr['dataentrada'],6,5)." <br>$arr[pescon] <br>$arr[usuario] </td>
						<td>".substr($arr['razaosocial'],0,17)." <br> <b>".substr($arr['nomeconta'],0,30)."</b></td>
						<td>  $arr[cpfcnpj] $arr[nomepessoa] </td>
						<td>  $arr[placacarro]</td>
						<td>  $arr[placareboque]</td>
						<td>  $arr[placasemireboque] </td>
						<td>  $arr[placaterceiroreboque]</td>

						<td>$arr[tempo] <img src='../0bmp/$arr[statusprotocolo].png' align='absmiddle' border='0'> <br> $arr[statusprotocolo]0%  $deletaficha </td>	
						<td><a href='#' onclick='pesquisador($arr[protocolo])'; ><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'> </a> $arr[pesquisador] </td> 
						<td><a href='#'  class='btn btn-warning btn-sm mb-2' onclick=onclick=pesquisa('$arr[cpfcnpj]','$arr[grupo]','$arr[protocolo]','$arr[codipessoa]','$arr[placacarro]','$arr[placareboque]','$arr[placasemireboque]','$arr[placaterceiroreboque]','$arr[conta]','$arr[contaprincipal]','consulta','$arr[enviaemailresposta]')>  Edita </a></td>




						
						</tr>";
						
						
//						<td><a href='#' class='btn btn-warning btn-sm mb-2' onclick=window.open('../-/interrisco/pendentes.php','','width=1380,height=1500')  > versao 8.25.9 </a></td>

				}	


				

//echo "<br><br> emai resposta $arr[enviaemailresposta]  <br>";

				
			
			}	
			
			//*
			//*pego o novo upload doc
			//*
			
		
			
			
		//***registros pendentes da tela do usuario
		}else{
		
			for ($i=0; $i < pg_numrows($res ); $i++) {
			
				$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
				
				//****************************
				//* pega os arquivos de upload
				//****************************
				$doc ="";	
				if ( strlen ($arr['copiadoc'] ) > 2 ) {
									
					$pieces = explode(";", $arr['copiadoc']);
					foreach($pieces as $arq){	
						if ($arq != '')						
							$doc .="<br><a href='../0uploaddoc/$arq' target='_blank'>".substr($arq,50)." </a> ";
					}				
				}
			
				if ($arr['placacarro'] != '') {
					
					$sqldoccarro = "select copiadoc from tcarro where placa = '$arr[placacarro]' ";
					$pgdoccarro = pg_exec($sqldoccarro);
					if ( pg_numrows($pgdoccarro) > 0 ){
						
						if ( strlen (pg_result($pgdoccarro,'copiadoc')  ) > 2 ) {
									
							$pieces = explode(";", pg_result($pgdoccarro,'copiadoc') );
							foreach($pieces as $arq){				
								if ($arq != '')	
									$doc .="<br><a href='../0uploaddoc/$arq' target='_blank'>".substr($arq,50)."</a> ";
							}
						}
					}					
				}
				
				if ($arr['placasemireboque'] != '') {
					
					$sqldoccarro = "select copiadoc from tcarro where placa = '$arr[placasemireboque]' ";
					$pgdoccarro = pg_exec($sqldoccarro);
					if ( pg_numrows($pgdoccarro) > 0 ){
						
						if ( strlen (pg_result($pgdoccarro,'copiadoc')  ) > 2 ) {
									
							$pieces = explode(";", pg_result($pgdoccarro,'copiadoc') );
							foreach($pieces as $arq){				
								if ($arq != '')	
									$doc .="<br><a href='../0uploaddoc/$arq' target='_blank'>".substr($arq,50)." </a> ";
							}
						}
					}					
				}		
				
				if ($arr['placareboque'] != '') {
					
					$sqldoccarro = "select copiadoc from tcarro where placa = '$arr[placareboque]' ";
					$pgdoccarro = pg_exec($sqldoccarro);
					if ( pg_numrows($pgdoccarro) > 0 ){
						
						if ( strlen (pg_result($pgdoccarro,'copiadoc')  ) > 2 ) {
									
							$pieces = explode(";", pg_result($pgdoccarro,'copiadoc') );
							foreach($pieces as $arq){				
								if ($arq != '')	
									$doc .="<br><a href='../0uploaddoc/$arq' target='_blank'>".substr($arq,50)." </a> ";
							}
						}
					}				
				
				}
				
				if ($arr['placaterceiroreboque'] != '') {
					
					$sqldoccarro = "select copiadoc from tcarro where placa = '$arr[placaterceiroreboque]' ";
					$pgdoccarro = pg_exec($sqldoccarro);
					if ( pg_numrows($pgdoccarro) > 0 ){
						
						if ( strlen (pg_result($pgdoccarro,'copiadoc')  ) > 2 ) {
									
							$pieces = explode(";", pg_result($pgdoccarro,'copiadoc') );
							foreach($pieces as $arq){				
								if ($arq != '')	
									$doc .="<br><a href='../0uploaddoc/$arq' target='_blank'>".substr($arq,50)." </a> ";
							}
						}
					}					
				}
				
				if ($arr['statusprotocolo'] > 10) {
					$arr['statusprotocolo'] = 10;
				}
							
				
				//aqui coloca a linha dos registros pendentes
				$a .= "<tr class='letra_gris'><td > $arr[dataentrada] $arr[pescon]</td><td > $arr[nomeconta]</td> "; 
				
				if ($arr['nomepessoa'] != '') 
					$a .= "<td><a href='#' onclick=\"window.open('../0irupload/uploadtela.php?&chavemotplaca=Condutor&chave=$arr[cpfcnpj]&codipessoa=$arr[codipessoa]','', 'width=500,height=400,location=no'); \"  >  $arr[nomepessoa]  </a>  </td>";
				else
					$a .= "<td></td>";
				
				if ($arr['placacarro'] != '') 
					$a .= "<td><a href='#' onclick=\"window.open('../0irupload/uploadtela.php?&chavemotplaca=Placa Veiculo&chave=$arr[placacarro]','', 'width=500,height=400,location=no'); \"  >  $arr[placacarro]  </a> </td>";
				else
					$a .= "<td></td>";
				
				if ($arr['placareboque'] != '') 
					$a .= "<td><a href='#' onclick=\"window.open('../0irupload/uploadtela.php?&chavemotplaca=Placa Reboque&chave=$arr[placareboque]','', 'width=500,height=400,location=no'); \"  >  $arr[placareboque]  </a> </td>";
				else
					$a .= "<td></td>";

				if ($arr['placasemireboque'] != '') 
					$a .= "<td><a href='#' onclick=\"window.open('../0irupload/uploadtela.php?&chavemotplaca=Placa SemiReboque&chave=$arr[placasemireboque]','', 'width=500,height=400,location=no'); \"  >  $arr[placasemireboque] </a> </td>";
				else
					$a .= "<td></td>";
				
				if ($arr['placaterceiroreboque'] != '') 
					$a .= "<td><a href='#' onclick=\"window.open('../0irupload/uploadtela.php?&chavemotplaca=Placa TerceiroReboque&chave=$arr[placaterceiroreboque]','', 'width=500,height=400,location=no'); \"  >  $arr[placaterceiroreboque] </a> </td> ";
				else
					$a .= "<td></td>";
				
				$a .= "<td><img src='../0bmp/$arr[statusprotocolo].png' align='absmiddle' border='0'> <br> $arr[statusprotocolo]0% $arr[protocolo] </td><td align='center'>$arr[tempo]</td><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'></td></tr>";
			
			}			
		}	
					
		
		$a .= "<tr class='letra_gris'><td class=menuiz_botonoff COLSPAN=9> </TD> </tr>";
				
		$a .= "</table>";
	} else {
	
		$a = faviso2("-","Nao consta registros pendentes neste momento ");
		
	}
	
	echo $a.$tl;

	echo ftela('registrospendentes versao 6.3.65');
	
						//************************************************************************ //
						//*                                                                      * //
						//*               T E L A   Q U E  R E A L I Z A   A P E S Q U I S A     * //
						//* DEFINE SE e UMA CONSULTA OU PESQUISA E DEFINE VALOR DA PESQUISA      * //
                        //*                                                                      * //   
						//************************************************************************ //
		
} else 	if ( $_GET['sq']	== 'baixaficha' ) {

	

	//abre colchetes na variavel $exibetexto pra o javascript entender que e um array

	$exibetexto = '';
	
		$sql = "
			update tchamada
			set  custo = '0' ,
				liberado =  null ,				
				senha  = '0',				
				statusprotocolo = '10',
				pescon = 'CAN',
				comissao = '0',
				datasaida = '".date('d/m/Y')."',
				resposta = 'PESQUISA CANCELADA em ".date('d/m/Y')." por $_SESSION[usuario] '
			where  protocolo = '$_GET[protocolo]'  ";
					
	$res = pg_exec($sql);

	$exibetexto .= "<table class='tabla_listado'>";


	if ( $res ){

		echo " Pesquisa cancelada com sucesso ! ";	

	} else {
		echo " Alerta: Nao foi possivel efetuar gravacao ! ";	
    }
	
} else if ( $_GET['sq']	== 'consultando' ) {

	//testo o dtpr envio de email perfil 
	//echo " <a href='#' onclick=gravacontroleconsultoria('enviodtpr@gmail.com','grcent2015','867497','PERFIL','workdep@gmail.com')>   <div id='buttonaz' style='display: inline' > .Perfill+ testo dtpr envio </div></a><br><br> ";
	
	// coloco uma vigencia padrao de 180 dias, se exisitir uma pessoa a vigencia podera ser menor senao sera de um ano
	$vigencia = 180; // vigencia de dias, se nao existir motorista a vigencia sera do veiculo de um ano
	$posmot = 't'; // validacao do motorista
	$poscarro = 't'; // validacao do placacarro
	$posreb = 't'; // validacao do reb
	$possemireb = 't'; // validacao do semireb	
	$posterceiroreb = 't'; // validacao do semireb	
	$poschamada = 'f';
	//data que vai ser gravado como saida da tchamada
	$datadesaida = date("d/m/Y");
	$horadesaida = date("H:i:s");
	//codigo da resposta
	$codresp = '';
	$debug = '';
	$avisos = '';// cria a tabela que informa avisos
	$email = 'interrisco@gmail.com';
	$enviarconsultoria = '';
	$obsreferencia = '';
	$auxobs = '';
    	
	$fazerexperiencia = 't';
	$fazerrdo = 't'; 
	$fazerreferencia = 't';
	
	$arr['cpfcnpj'] = '';
	$debug= '';
	$sql='';
		
	// este select serve para pegar tchamada novamente, calcular o tempo novamente e data de entrada,
	// os dados de tchamada n�o to conseguindo trazer como parametro na funcao pq da erro de espa�o em branco
	// nos campos caracter
	// eu nao trago placas e codigos de proprietarios, pq ja ta vindo via get.
	// busco aqui tparametrocadastro para pegar tb os parametro da placa, tipo cheque proprietario se tiver
	
	
	
	$sqla = "			
		select 
			tchamada.protocolo,
			tchamada.pescon,
			tchamada.conta,
			tchamada.contaprincipal,			
			tchamada.usuario,			
			tchamada.validade,
			tchamada.senha,
			tchamada.pesquisador,
			tchamada.pacote,
			tchamada.resposta,
			tchamada.statusprotocolo,			
			tchamada.tipovinculo,			
			tchamada.totalserasa,
			tchamada.totalpontoscnh,
            tchamada.obsresposta,
			tchamada.codipessoa,
			tchamada.datasaida,			
			to_char(dataentrada, 'DD/MM HH24:MI') as dataentrada,
            to_char(tchamada.dataentrada, 'DD-MM-YYYY  HH24:MI') as dataentradacalculo,
			to_char((current_timestamp - dataentrada), 'HH24:MI') as tempo,
			tparametrocadastro.qtdchequefuncionario,
			tparametrocadastro.qtdchequeagregado,
			tparametrocadastro.qtdchequeautonomo,
			tparametrocadastro.vigenciafuncionario,
			tparametrocadastro.vigenciaagregado,
			tparametrocadastro.vigenciaautonomo,
			tparametrocadastro.custopesquisa,			
			tparametrocadastro.renovaautomatico,			
			tparametrocadastro.idademaximacarro,
			tparametrocadastro.prazonovasenha,
			tparametrocadastro.limiteserasa,			
			tparametrocadastro.demaisregras,
			tparametrocadastro.bloqueiaspot,
			tparametrocadastro.rdoautomatico,
			tparametrocadastro.rdopesquisavinculo,
            tparametrocadastro.diasvigenciaconsulta,
			tparametrocadastro.antt as parametroantt,
			tparametrocadastro.fazerreferencia,
			tparametrocadastro.fazercheque,
			tparametrocadastro.validaragregado,
			tparametrocadastro.obrigabiometria,	
			tparametrocadastro.enviaemailresposta,
			tparametrocadastro.tj,
			tcontaprincipal.razaosocial,
			tcontaprincipal.maillogo,
			tconta.fone,
			tconta.nextel,
			tconta.nomeconta,
			tconta.email,
			tconta.grupo,
			tconta.grupoprincipal,
			tconta.cnpjglog,
			tconta.senhaglog,
			tconta.percentualcomissaogestor,
			tconta.cnpjglog,
			tconta.senhaglog
			
			
		from
			tconta,
			tcontaprincipal,
			tparametrocadastro,
			tchamada
			
		where tchamada.protocolo = $_GET[protocolo] and
			tchamada.conta = tconta.conta and			
			tchamada.conta = tparametrocadastro.conta and
			tchamada.contaprincipal = tcontaprincipal.contaprincipal	";

	$res = pg_exec($sqla);
		
	$debug .= $sql;
	
	if ( pg_numrows($res) > 0 ){

		$arr = pg_fetch_array($res,0,PGSQL_ASSOC);
		
		
		if ( $arr['statusprotocolo'] == 10 &&  ($arr['liberado'] == 't' or $arr['liberado'] == 'f' ) )
			die("<div class='alert alert-danger' role='alert'> Registro ja foi respondido </div>");
	    
        // consulta 24 horas mas tem cliente com 90 dias de vigencia para consulta
        $arr['diasvigenciaconsulta'] = (int)$arr['diasvigenciaconsulta'];
        
        // se for zero, coloca como vigencia 24 horas
        if ($arr['diasvigenciaconsulta'] < 1)
            $arr['diasvigenciaconsulta'] = 1;    
    
		//coloco pesquisador para 2 usuarios nao  ficar pesquisando a mesma coisa
		// verifico se ja tem pesquisador consultando senao atribo o primeiro
		
		if (($_SESSION['usuario'] != 'asd' ) &&			
			($_SESSION['usuario'] != 'taticad' ) &&
			($_SESSION['usuario'] != 'laura' ) &&
			($_SESSION['usuario'] != 'isis' ) &&
			($_SESSION['usuario'] != 'joaopaulo' ) &&			
			($_SESSION['usuario'] != 'basecaio' ) )		{
						
			if ( $arr['protocolo'] != '') { 
				vinculapesquisador($arr['protocolo']); 
			} 	
			
		}	
			
		// esta e a tabela de cabecalho
		$a = "<table border='0'  align=center width='100%' >";
		$a .="<tr class='moduleTitle'><td colspan=3>$arr[razaosocial] $arr[nomeconta] </td> 					
					<td > Entrada $arr[dataentrada] <br> Demora $arr[tempo] </td>	</tr>";
		$a .= "</table>";
		
		$debug .= "<br> variavel protocolo $_GET[protocolo] ";
				
		
		//***********************		
        //  				    *
		// consulta motorista   *
		//                      *
        //***********************

		$debug .= "<br> codipessoa $_GET[codipessoa] <br>";
		
		if (strlen($_GET['codipessoa']) > 0 ) {
		
			// este select serve para trazer os dados do motorista e o tipo de vinculo, e tb a checagem do motorista.
			// vou ter que pegar tpessoavinculo de tchamada pq se o cliente altera
						
			$sqlpes = "
			
				select 					
				    tpessoa.obsficha,
					tpessoa.codipessoa,
					tpessoa.nomepessoa,
					tpessoa.cep,
					tpessoa.cidade,
					tpessoa.endereco,			
					tpessoa.cpfcnpj,
					tpessoa.uf,			
					tpessoa.fone,
					tpessoa.celular,
					tpessoa.email,					
  				    tpessoafisica.rg,
				    tpessoafisica.ufrg,
				    tpessoafisica.ufnascimento,
				    tpessoafisica.cidadenascimento,
				    tpessoafisica.dtnascimento,
				    tpessoafisica.datavalidadecnh,
				    tpessoafisica.ufcnh,
				    tpessoafisica.categoria,
					tpessoafisica.cedulacnh,
				    tpessoafisica.numregistro,
				    tpessoafisica.nomepai,
				    tpessoafisica.nomemae,
					tpessoafisica.renach,
					tpessoafisica.primeirahabilitacao,
					tpessoavinculo.tipovinculo,
					tvalidapessoa.codipessoa as pessoack,
					tvalidapessoa.ckdata,					
					tvalidapessoa.ckcheque,
					tvalidapessoa.ckserasa,
					tvalidapessoa.qtdcheque,					
					tvalidapessoa.ckconsultoria,
					tvalidapessoa.ckcnh,					
					tvalidapessoa.obs,
					tvalidapessoa.ckreceita,
					tvalidapessoa.risco,					
					tvalidapessoa.ck,
					tvalidapessoa.ckfone,
					tvalidapessoa.cktj,
					to_char((current_timestamp - ckdata), 'DD') as pesquisadodiasatraz,
					tpessoafisica.numsegurancacnh,					
					tvalidapessoa.consultoriadata,
					tvalidapessoa.consultoriausuario,
					tvalidapessoa.receitadata,
					tvalidapessoa.receitausuario,
					tvalidapessoa.fonedata,
					tvalidapessoa.foneusuario,
					tvalidapessoa.chequedata,
					tvalidapessoa.chequeusuario,
					tvalidapessoa.serasadata,
					tvalidapessoa.serasausuario,
					tvalidapessoa.cnhdata,
					tvalidapessoa.totalserasa,
					tvalidapessoa.totalpontoscnh,
					tvalidapessoa.cnhusuario,					
					tvalidapessoa.experienciausuario,
					tvalidapessoa.experienciadata,
					tvalidapessoa.experienciack,
					tvalidapessoa.ckrdo,
					tvalidapessoa.rdodata,
					tvalidapessoa.rdousuario,
					tvalidapessoa.ckfacial,
					tvalidapessoa.facialdata,
					tvalidapessoa.facialusuario,
					tvalidapessoa.cktj,
					tvalidapessoa.tjdata,
					tvalidapessoa.tjusuario,
					to_char((cnhdata - current_timestamp), 'DD') as checkcnhdata,
					to_char((datavalidadecnh - current_timestamp ), 'DD') as validadecnhdias,
					tdoc.tipodoc,
					tdoc.quantidade,
					tdoc.extensao,
					tdoc.copiadocurl,
					to_char((current_timestamp - tdoc.dataentrada), 'DD') as tdocdataentradadias,
					to_char(tdoc.dataentrada, 'DD/MM/YY HH24:MI') as tdocdataentrada
				
				from
					
					tpessoavinculo,
					tpessoafisica LEFT OUTER JOIN tvalidapessoa ON (tpessoafisica.codipessoa = tvalidapessoa.codipessoa), 
					tpessoa LEFT OUTER JOIN tdoc ON (tpessoa.codipessoa = tdoc.codipessoa)

				where tpessoa.codipessoa = $_GET[codipessoa] and
					tpessoa.codipessoa = tpessoafisica.codipessoa and
					tpessoa.codipessoa = tpessoavinculo.codipessoa and
					tpessoavinculo.contaprincipal = $_GET[contaprincipal] ";

			$respes = pg_exec($sqlpes);	
			
			$arrpes = pg_fetch_array($respes,0,PGSQL_ASSOC);			
						
			$debug .= "<br> contaprincipal $_GET[contaprincipal] <br>";
			
			$debug .= "<br> codigo sql do motorista <br> $sqlpes <br>";
					
			// aqui eu testo se da para liberar o motorista ou nao.			
			// se o ck for diferente de verdadeiro posmot sera falso
						
			// o serasa possui uma condicao diferente
						
			$arr['limiteserasa'] = str_replace('.', '',$arr['limiteserasa']);
			$limiteserasa = (int)str_replace('R$ ', '',$arr['limiteserasa']);

			//verifico se é funcionario agregado para aparecer o botao experiencia e rdo
			if  (   $arr['contaprincipal'] == '855705' && ( $arr['tipovinculo'] == 'FUNCIONARIO'  )  ) {

				$fazerrdo = 'f'; 				

			}			
						
			//***********************
			// procedimento prysmian
			//***********************
						
			// se for prysmian tem que fazer rdo primeira viagem e renovacao
			//PARA RENOACOES E PESQUISAS para prysmian
			//se for uma conta prysmian
			if 	(($arr['pescon'] != 'PES') &&  							
				(strpos($arr['nomeconta'], 'PRYSMIAN') !== false) &&
				($arr['tipovinculo'] != 'FUNCIONARIO')		) {

				$sqlprimeirapesq = "
				
					select liberado					
					from tchamada,
						tconta
					where tchamada.conta = tconta.conta and	
						nomeconta like 'PRYSMIAN %' and
						liberado is not null and
						tchamada.codipessoa = $arr[codipessoa]
					order by protocolo desc						
						
				";
				
				$respprimeirapesq = pg_exec($sqlprimeirapesq);	
			
				if ( pg_numrows($respprimeirapesq) < 1 ){
					
				//if (strlen($_GET['codipessoa']) > 0 ) {
			
				//$arrpes = pg_fetch_array($respes,0,PGSQL_ASSOC);	

					echo "<div class='alert alert-borda' style='border-color: black;' role='alert'> 
						<h3>.: ATENCAO ! :. Operacao Prysmian</h3> Detectado motorista primeira viagem
						</div>";
						
					echo "<div class='alert alert-borda' style='border-color: danger;'> 
						PRYSMIAN PRIMEIRO CARREGAMENTO E RENOVACAO :. -
						PRECISA FAZER RDO PARA MOTORISTA E PROPRIETARIO 
						UF RESIDENCIA MOTORISTA E RESIDENCIA PROPRIETARIO 
						RESPOSTA NAO VAI PARA TRANSPORTADORA, VAI PARA O NIVALDO KRONA 
						nivaldo.cordeiro@kronamaxi.com.br; dionatan.barros@kronamaxxi.com.br
						Aguardando RDO Responder: Pesquisa em analise
						Negativado RDO Responder: Em analise contactar o embarcador
						</div>";						
					
					$fazerrdo = 't'; 	
					
				}else{
					
					if ( pg_numrows($respprimeirapesq) == 1 ){
						
						$arrprimcarr = pg_fetch_array($respprimeirapesq,0,PGSQL_ASSOC);	
						
						
						if ($arrprimcarr == 'f' ){
							
							echo "<div class='alert alert-borda' style='border-color: yellow;'> 
								<h3>.: ATENCAO ! :. Operacao Prysmian</h3> Detectado motorista primeira viagem						
								</div>";
						
							echo "<div class='alert alert-borda' style='border-color: yellow;' role='alert'> 
								PRYSMIAN PRIMEIRO CARREGAMENTO E RENOVACAO :. -
								PRECISA FAZER RDO PARA MOTORISTA E PROPRIETARIO 
								UF RESIDENCIA MOTORISTA E RESIDENCIA PROPRIETARIO 
								RESPOSTA NAO VAI PARA TRANSPORTADORA, VAI PARA O NIVALDO KRONA 
								nivaldo.cordeiro@kronamaxi.com.br; dionatan.barros@kronamaxxi.com.br
								Aguardando RDO Responder: Pesquisa em analise
								Negativado RDO Responder: Em analise contactar o embarcador
								</div>";	
							
							$fazerrdo = 't';
							
						}	
						
					}
				
				}				
				
			}			
			
			//*************************//
			//* procedimento cargopag *//
			//*************************//
			
			//conta cargopag 2207041816   cargopag mterram 2302011841
			//conta meg 2301021656   megmterram 2302021027    
			
			if  ( (	$arr['conta'] == '2207041816' || 
					$arr['conta'] == '2302011841' ||
					$arr['conta'] == '2301021656' || 
					$arr['conta'] == '2302021027') && 
					( $arr['tipovinculo'] == 'AUTONOMO') ) {

				/*
				Frotas:	Validade da liberação de 12 meses;
				Agregados:	Motorista- se tiver 12 viagens ou mais no último ano com a CargoPag, fica liberado
							por 6 meses (validade do cadastro).  
							Proprietário- se for Pessoa JURIDICA, e o proprietario tiver 25 viagens ou mais no 
							último ano, seu motorista e veiculo são liberados por 6 meses, independentemente de 
							o motorista ter viagens ou não.

				Mot autônomos: Consulta- a cada viagem (validade 2 dias no sat, esse já está funcionando)
							Referencias- mínimo de 8 viagens nos últimos 6 meses na flex

							-OU 3 viagens do motorista na Cargopag nos últimos 2 meses.

							Caso o motorista não seja agregado, se ele for autônomo e NÃO TENHA REFERENCIA 
							confirmada,  já DEIXAR AUTOMATICO NO SISTEMA DE VOCÊS A SOLICITAÇÃO DA VITIMOLOGIA, 
							conforme o Sandro já havia dito ser possível.  FULL
				*/
				

				$sqlviagem = "select * from tqtdviagem where cpfcnpj = '$arrpes[cpfcnpj]' ";
								
				$rescp = pg_exec($sqlviagem);	
			
				$viagemcp = pg_fetch_array($rescp,0,PGSQL_ASSOC);	
				
				
				if ( (int)$viagemcp['qtdviagem'] <= 3 ) {
				//if ( 3 >= 3 ) {
					
					$fazerrdo = 't'; 	
					//$fazerexperiencia = 't';
					$arr['rdoautomatico'] = 't';					
					
					$avisos .= "<br><div class='alert alert-borda' style='border-color: yellow;' role='alert'>
					
							ATENCAO AUTONOMO cargopag com menos de 3 viagens: $viagemcp[qtdviagem]

							SE NAO TIVER mínimo de 8 viagens nos últimos 6 meses na FLEX 	
							
							fazer rdo FULL
												
					</div> ";
					
				}			
				
			}
						
			
			if ( ($arr['tj'] == 't')  && ($arr['pescon'] != 'CON') ) { 
			
				if ($arrpes['cktj'] != 't' ) 
					$posmot = 'f';	
				
			}

			// a protege pesquisa só tj
			if ( $_GET['contaprincipal'] != 920854) { 

				
				if  (   $arr['contaprincipal'] == '855705' && ( $arr['tipovinculo'] == 'FUNCIONARIO'  )  ) {

					$fazerexperiencia = 'f'; 				

				}
				
				
				//prysmian nao precisa fazer experiencia PROPRIETARIO
				//ECHO $arr['grupo'];
				//ECHO$arr['tipovinculo'];
				
				if  (   $arr['grupo'] == 'PRYSMIAN' && ( $arr['tipovinculo'] == 'FUNCIONARIO'  )  ) {

					$fazerexperiencia = 'f'; 				
					$arr['bloqueiaspot'] = 'NAO';
					$fazerrdo = 'f' ;
					
				}			
				
				
				// verifico se é para fazer referencia
				if (  $arr['fazerreferencia'] == 'SIM') {
					
					if ( $arr['tipovinculo'] != 'FUNCIONARIO')  {
						
						//indico para fazer referencia e mostrar botao
						$fazerreferencia = 't';
						
						
						// se o ck fone nao for verdadeiro
						if ( $arrpes['ckfone'] != 't' ) 
							$posmot = 'f';	
										
					} else {
						
						// indico para nao fazer referencia nem mostrar botao
						$fazerreferencia = 'f';
					}	
					
				}  else {
					
					//entao nao precisa fazer referencia em nenhum tipo de motorista
					$fazerreferencia = 'f';
					
				}		

				// se o limite serasa for inferior a 50000 enota
				// pode consultar 	
				if ( $limiteserasa < 49000)  	 {			
				
					if ($arrpes['ckserasa'] != 't') 
						$posmot = 'f';			

									
				} else {
				
					$arrpes['ckserasa']	= 't';		
					
				}	
				
				
				
				if ($arrpes['ckconsultoria'] != 't' ) 
					$posmot = 'f';			
		
				if ($arrpes['ckcnh'] != 't' ) 
					$posmot = 'f';			
					
					
				if ( $arr['obrigabiometria'] == 't' ) { 
				
					if ($arrpes['ckfacial'] != 't' ) 
						$posmot = 'f';		
					
				}	
			
				if ($arrpes['ckreceita'] != 't' ) 
					$posmot = 'f';		
				
			}

//echo "<br> 5	posmot $posmot ";			

 //[ckcheque] => [ckserasa]

			// estou cancelando o cheque
			$arr['fazercheque'] = 'f';

			if ( $arr['fazercheque'] == 'f' )  	 {
				
				$arrpes['ckcheque'] = 't';
				$arrplacacarro['ckcheque']  = 't';
				$arrplacareboque['ckcheque']  = 't';
				$arrplacasemireboque['ckcheque']  = 't';
				$arrplacaterceiroreboque['ckcheque']  = 't';	
				
			}else{	
				
				if ($arrpes['ckcheque'] != 't') 
					$posmot = 'f';			
						
								
			}
			
			//VERIFICA SE FOI PRIMEIRO CARREGAMENTO FOTOS
			if ( ($arr['bloqueiaspot'] == 'SIM') && ($fazerexperiencia == 't')) {	
			
				//bloqueia por fotos ou alguma restricao primeia viagem (sem experiencia)
				if ($arrpes['experienciack'] != 't' ) 
					$posmot = 'f';				

			}
			
			//VERIFICA SE PRECISA COBRAR O RDO
			if (( $arr['rdoautomatico'] == 't') && ($fazerrdo == 't' ) ) {	

				//cliente que precisa de rdo	
				if ($arrpes['ckrdo'] != 't' ) 
					$posmot = 'f';
				
			}
						
			//*********************
			// BLOQUEIA CNH VENCIDA
			//*********************
			// se a cnh est� vencido entao starta,se for negativo � por que validade ta vencida
			// se maior que zero entao ja foi clicado (para nao ficar repitindo a verificacao em varios cliques)


						
			// e nao precisao checar novamente
			
			//o guilherme pediu para fazer somente o criminal do nao aco fob
			// dia 22/12/2020
							
				
			if ( ( (int)$arrpes['checkcnhdata'] < 0 )  ) {
							
							
				// EU BLOQUEIO A CNH SE PASSAR 			
				if ( 	((int)$arrpes['validadecnhdias'] < -365 ) and					
						( strlen($arrpes['numregistro']) > 0 ) ) {
				
					// se os dias sao negativos entao ja venceu a carteira
					if (  ( $arrpes['validadecnhdias'] <= 0 ) ) {
				
				
						$avisos .= "							
							<div class='alert alert-danger' role='alert'>
								<img src='../0bmp/a.png'  width='30' height='30' border=0 > ALERTAS - CNH <br>
								CADASTRO BLOQUEADO - CNH VENCIDA
							</div>	
							";											
						
						$arrpes['ckcnh'] = 'f';
						//$posmot = 'f';												
											
						//gravo a mensagem
										
						$sqlexp = " update tvalidapessoa 
							set ckcnh = 'f' ,					
								cnhdata = '".date('d/m/Y')."',
								cnhusuario = '$_SESSION[usuario]',
								obs = ('(".date('dmy')." $_SESSION[usuario]) CNH VENCIDA;' || obs)								
							where codipessoa = '$_GET[codipessoa]' ";

						$pgexp = pg_exec($sqlexp);


						$sqlchamada = "
							update tchamada
							set resposta = ('* CNH VENCIDA;' || resposta)
							where protocolo = '$arr[protocolo]'  ";
										
						$reschamada = pg_exec($sqlchamada);

						
						$arrpes['ckcnh'] = 'f';
						$arrpes['cnhdata'] = date('d/m/Y');
						$arrpes['cnhusuario'] = $_SESSION['usuario'];
							
					}				
				}
			}		
			
			//*************************************************************
			// verifica se tem que bloquear agregado que nao tem historico de carregamento
			//*************************************************************
			
			
			if ($arr['validaragregado'] == 'SIM') {
				
				
				$avisos .= "							
					<div class='alert alert-borda' style='border-color: red;' role='alert'>
						<img src='../0bmp/a.png'  width='30' height='30' border=0 > ALERTAS - SE REALENTE É AGREGADO <br>
						VERIFICAR SE AGREGADO TEM CONTRATO DE AGREGADO OU VERIFICAR HISTORICO PRECISA TER 12 CARREGAMENTOS PARA SER AGREGADO					
						NAO LIBERAR SEM TER HISTORICO DE CARREGAMENTO 
					</div>		";
			
			}	
						
			// elimina o para agregado e funcionario csn o botao experiencia
						
			//*************************************************************
			// verifica se tem que bloquear motorista spot(primeira viagem)
			//*************************************************************
			if ( ( $arr['bloqueiaspot'] == 'SIM')  && ($fazerexperiencia == 't') ) {
						
				if ($arrpes['experienciack'] != 't' ) {		
				
					$sqlspot = "
					
						select codipessoa
						from tchamada,
							tconta
						where tchamada.conta = tconta.conta and
							tconta.grupo = '$arr[grupo]' and
							tchamada.liberado = 't' and
							tchamada.codipessoa = $_GET[codipessoa]";
					
					$resspot = pg_exec($sqlspot);
		
					if ( pg_numrows($resspot) < 1 ){
				
				
						$avisos .= "							
							<div class='alert alert-borda' style='border-color: yellow;' role='alert'>
								<img src='../0bmp/a.png'  width='30' height='30' border=0 > ALERTAS - BLOQUEIO PRIMEIRO CARREGAMENTO <br>
								CADASTRO BLOQUEADO POR PRIMEIRO CARREGAMENTO (experiencia), nao liberar motorista 
							</div>		";
					
			

						if ($arrpes['experienciack'] != 't' ) {						
							$posmot = 'f';												
						}	
						
//echo "<br>	9 posmot $posmot ";															
//echo " <br> experienciack $arrpes[experienciack]  ";
//echo " <br> posmot $posmot  ";
						
						
						// se tem que bloqueado primeiro carregamento
						// ja grava na tabela
						//PEDIRAM PARA NAO GRAVAR
						/*
						if ($arrpes['experienciack'] == '' ) {
						
							$sqlexp = " update tvalidapessoa 
								set experienciack = 'f' ,					
									experienciadata = '".date('d/m/Y')."',
									experienciausuario = '$_SESSION[usuario]',
									obs = ('(".date('dmy')." $_SESSION[usuario]) CADASTRO BLOQ POR PRIMEIRO CARREGAMENTO;' || obs)								
								where codipessoa = '$_GET[codipessoa]' ";
		
							$pgexp = pg_exec($sqlexp);

							$sqlchamada = "
								update tchamada
								set resposta = ('* CADASTRO BLOQ POR PRIMEIRO CARREGAMENTO;' || resposta)
								where protocolo = '$arr[protocolo]'  ";
											
							$reschamada = pg_exec($sqlchamada);
							
							$arrpes['experienciack'] = 'f';
							$arrpes['experienciadata'] = date('d/m/Y');
							$arrpes['experienciausuario'] = $_SESSION['usuario'];

						}
						*/
						
					}		
				}	
			}
						
			//********************************************************
			//****** se tem obs na pgr/ regras particulares para conta
			
			if ( $arr['demaisregras'] != '') { 
			
				$a .="<table align=center width='100%' >";
				$a .="<tr class='botonmoduleon'><td>";			
				$a .= "<img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle'> <b>ATENCAO ! </b> SEGUIR CONDICOES: \n";			
				$a .= $arr['demaisregras'];				
				$a .="</td></tr></table>";
							
			}
			

			
		
			
			
			$a .="<table border=1 class='tabla_cabecera' border='0'  align=center width='100%' >";			
			$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=4><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'>CPF: ($arrpes[cpfcnpj]) &nbsp;&nbsp; <b> $arrpes[nomepessoa] </b> &nbsp;&nbsp; $arr[tipovinculo] </td></tr>";
//				$a .="<tr><td> Cpf  </td><td> $arrpes[cpfcnpj]</td>";
//			$a .="	<td> Nome </td><td> $arrpes[nomepessoa] </td></tr>";
			$a .="<tr><td> End. </td><td> $arrpes[endereco]</td>";
			$a .="	<td> Cidade </td><td> $arrpes[cidade] - $arrpes[uf]</td>";				
			$a .="<tr><td> Fone </td><td> $arrpes[fone]</td>";     
			$a .="	<td> Celular </td><td> $arrpes[celular] </td>	</tr>";   
			$a .="<tr><td> RG </td><td> $arrpes[rg] - $arrpes[ufrg]  </td>";
			$a .="    <td> Nasc </td><td> $arrpes[dtnascimento] $arrpes[cidadenascimento] - $arrpes[ufnascimento] </td></tr>";
			$a .="<tr><td> Pai </td><td> $arrpes[nomepai]</td>";
			$a .="	<td> Mae </td><td> $arrpes[nomemae]</td></tr>";
			$a .="<tr><td> CNH </td><td> $arrpes[numregistro] - $arrpes[ufcnh] - Em: $arrpes[primeirahabilitacao] Renach: $arrpes[renach] Ced $arrpes[cedulacnh]</td>";
			$a .="	<td> Val.CNH </td><td> $arrpes[datavalidadecnh] - CAT $arrpes[categoria] - Seguranca CNH $arrpes[numsegurancacnh] </td></tr>";
			$a .="</table>";
			
			// verifico se a cnh ta vencida	
			
			$data1 = $arrpes['datavalidadecnh'];
			$hoje = date('Y-m-d');

			//converto para formato linux '-'
			$data1 = implode('-', array_reverse(explode('/', $data1)));


			//coloco mais 29 dias na data
			// que a lei permite para rodar com ela vencida
			// se a data da cnh mais 29 dias for menor que a data de hoje
			//entao a cnh vencida
			if ( strtotime($data1.'+ 29 days') <= strtotime($hoje) ) {

			  echo "<div class='alert alert-borda' style='border-color: yellow;' role='alert'> 
				<h1>::: ATENCAO ::: A CNH ESTÁ VENCIDA !! </h1>
				</div>";

			}
			
			
			if ( $arrpes['obsficha'] != '' ) {
				$a .="<tr><td > Obs.: </td><td colspan=3 class=menuiz_botonoff> $arrpes[obsficha] </td></tr>";
			}	
					
			
			//***********************************************
			// AVISOS 
			//***********************************************
			
			$sqlpesocorrencia = "
				select obs,
					to_char(datacriacao, 'DD/MM/YY') as datacriacao,
					usuario
				from tocorrencia
				where trim(chavedebusca) = '$_GET[codipessoa]'
				order by codiocorrencia desc";
			
			$ressqlopescorrencia = pg_exec($sqlpesocorrencia);	
			
			if ( pg_numrows($ressqlopescorrencia) > 0 ) {
			
			
				$avisos .= "							
					<div class='alert alert-borda' style='border-color: yellow;' role='alert'>
						<img src='../0bmp/a.png'  width='30' height='30' border=0 > ALERTAS - Anotacoes operador <br>
					";
					
				
				for ($i=0; $i < pg_numrows($ressqlopescorrencia ); $i++) {

					$arrpesocorrencia = pg_fetch_array($ressqlopescorrencia,$i,PGSQL_ASSOC);
					$avisos .= "$arrpesocorrencia[datacriacao] $arrpesocorrencia[usuario] - $arrpesocorrencia[obs] \n";
				}
				
				$avisos .= "</div>";

			}
						
			//******************************
			//*  verifica alerta blacklist
			//*******************************
			$msgalerta = '';
			$msgalerta = puxablacklistpessoa($_GET['codipessoa'],''); 
									
			//*************************
			//* cria a tela de consulta 
			//*************************			
			// atencao a div botaoazul � a div que cria o botao esta em //0layout/elastix/style.css
	
		    //tira a ;e insere <br> para quebrar linha no html
			if ($arrpes['obs'] != '')
				$auxobs .= "<div class='alert alert-borda' style='border-color: yellow;' role='alert'>".str_replace(";","<br>",$arrpes['obs'])."</div>";

		    // a funcao gravavalidapessoaobs esta declarada em registrospendentes.php		
			// div_observacao_motorista serve para visualizar os campos para gravar observacao do motorista

								
			
			$a.="<a name='ancoramotobs' id='ancoramotobs'></a>";	




			$a .="<table width='100%' cellpadding='3' > ";
			//ANEXOS DA FICHA
			if ( $arrpes['copiadocurl'] != '' || $arrpes['tipodoc'] != '') 
				$a .= "<tr><td><div class='alert alert-borda' style='border-color: blue;' role='alert'> Anexo(s): ".criabotaoanexo($respes,'cpf')."</div></td></tr>";
			$a .="</table>";




			$a .="<table width='100%' cellpadding='3' > ";
			$a .="<tr><td><div class='alert alert-borda' style='border-color: yellow;' role='alert'> $msgalerta </div>	</td><td rowspan=5> <a href='#' onclick=historicocarregamentos('$arrpes[cpfcnpj]'); class='btn btn-success btn-sm mb-2'>  																		<img src='../0bmp/prancheta.jpg' width='20' height='20' align='absmiddle'  > 											Ver os Historicos</div></a><br>
															<a href='../0ocorrencia/criaocorrencia.php?chavedebusca=$_GET[codipessoa]&criterioporget=CODIPESSOA' target = '_blank' class='btn btn-success btn-sm mb-2' > 				<img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle' target = '_blank' > 				Gera novo Alerta </a><br>
															<a href='../-/interrisco/alterarespostapesquisa.php?&tipo=motorista&codipessoa=$_GET[codipessoa]&obs=$arrpes[obs]' target = '_blank' class='btn btn-success btn-sm mb-2'>	<img src='../0bmp/interno.gif' width='15' height='15'  border='0' align='absmiddle'> 									Editar mensagens </a><br>
															<a href='#' onclick=pesquisa('$arrpes[cpfcnpj]','$_GET[grupo]','$_GET[protocolo]','$_GET[codipessoa]','$_GET[placacarro]','$_GET[placareboque]','$_GET[placasemireboque]','$_GET[placaterceiroreboque]','$_GET[conta]','$_GET[contaprincipal]','consulta','$_GET[enviaemailresposta]') class='btn btn-success btn-sm mb-2' > 	Atualiza Pagina (F5 )</a>															
															</td></tr>";			
			$a .="<tr><td> $avisos 		</td></tr>";
			$a .="<tr><td> 	$auxobs 	</td></tr>";			
			$a .="<tr><td>  <div id='div_observacao_motorista'></div></td></tr>";
			$a .="<tr><td>  <div id='divobs'></div> </td></tr>";
			$a .="</table>";
			
			//divcontroleconsultoria - e a div que vai dizer se foi gravado em tcontroleconsultoria ou nao.

			// verifica quantos cheque pode ter
			
			$toleranciachque= '';
			
			if (trim($arr['tipovinculo']) == 'FUNCIONARIO' || $arr['tipovinculo'] == 'INTERNO' ) {
				$toleranciachque = $arr['qtdchequefuncionario'];
				$vigencia = $arr['vigenciafuncionario'];				 
			} else if ($arr['tipovinculo'] == 'AGREGADO' || $arr['tipovinculo'] == 'PROPRIETARIO' ||  $arr['tipovinculo'] == 'AUXAGREGADO' ) {
				$toleranciachque = $arr['qtdchequeagregado'];
				$vigencia = $arr['vigenciaagregado'];
			} else if ($arr['tipovinculo'] == 'AUTONOMO' || $arr['tipovinculo'] == 'AJUDANTE' ) {
				$toleranciachque = $arr['qtdchequeautonomo'];
				$vigencia = $arr['vigenciaautonomo'];
			}	
					
			$custopesquisa =  str_replace("R$ ","",$arr['custopesquisa']);
			
			//-----------------------------
			// coloca botoes para consulta
			//-----------------------------
			
			//botao do MOTORISTA			
			$a.="<table align='right' cellpadding='5' ><tr>	 ";
							
				//geral
				if ($arrpes['ckdata'] == '') 
					$a.="		<td align=center ><a href='pesquisas.php' target='_blank' class='btn btn-light btn-sm ><img src='../0bmp/$arrpes[ck]a.png' align='absmiddle' border='0' width='40' height='40'> <br> $arrpes[ckdata] <br>ha $arrpes[pesquisadodiasatraz] dias </a> ";
				else
					$a.="		<td align=center  ><a href='pesquisas.php' target='_blank' class='btn btn-light btn-sm ><img src='../0bmp/$arrpes[ck].png' align='absmiddle' border='0' width='40' height='40'> <br>$arrpes[ckdata] <br>ha $arrpes[pesquisadodiasatraz] dias </a> ";

				// tribunal de justica tj
								
				if ( ($arr['tj'] == 't')  && ($arr['pescon'] != 'CON') ) { 

					if ( $arrpes['cktj'] == 't')
						$corbotao = 'success';
					else
						$corbotao = 'danger';

					if ( $arrpes['tj']  == 't') 
						$a.="	 	<td align=center ><div id='divtj'>  <a href='#ancoramotobs' class='btn btn-$corbotao btn-sm mb-1' onclick=fun_obs_tj_motorista('$_GET[codipessoa]','$arr[protocolo]')><img src='../0bmp/$arrpes[cktj].png' align='absmiddle' border='0' width='40' height='40'> TJ Trib. Justica <br> Agora <br> $arrpes[tjusuario] </a></div></td>";
					else
						$a.="	 	<td align=center><div id='divtj'>  <a href='#ancoramotobs' class='btn btn-light btn-sm mb-1' onclick=fun_obs_tj_motorista('$_GET[codipessoa]','$arr[protocolo]')><img src='../0bmp/$arrpes[cktj]a.png' align='absmiddle' border='0' width='40' height='40'>  TJ Trib. Justica  <br> $arrpes[tjdata] <br> $arrpes[tjusuario]</a> </div></td>";
				
					
					if ( true ) {
								
							$a.="<td align=center > <a href='#ancoramotobs' class='btn btn-$corbotao btn-sm mb-1' onclick=\"window.open('../juridico/test/index.php?&protocolo=$arr[protocolo]&cpf=$arrpes[cpfcnpj]&codipessoa=$arrpes[codipessoa]&conta=$arr[conta]&nomepessoa=$arrpes[nomepessoa]','','width=1200,height=800'); alterabotaopesqtj();\"> <div id=divpesqtj name=divpesqtj>Pesquisar tj </div></a></td>";
							
							
					}	
				}	
					
						/*$datahj = date('d/m/Y');

						$databanco = DateTime::createFromFormat('d/m/Y', $arrpes['tjdata'] );
						$datahoje = DateTime::createFromFormat('d/m/Y', $datahj);

						$intervalo = date_diff($databanco, $datahoje);

						echo "Diferença em dias: " . $intervalo->days;

						if ($intervalo->days < 1 ) {
						
							$a.="	 	<td align=center > <a href='#ancoramotobs' class='btn btn-$corbotao btn-sm mb-1' onclick=\"window.open('../juridico/test/index.php?&protocolo=$arrpes[protocolo]&cpf=$arrpes[cpfcnpj]&codipessoa=$arrpes[codipessoa]&conta=$arr[conta]&nomepessoa=$arrpes[nomepessoa]','','width=1200,height=800')\"> Pesquisar tj </a></td>";
						
						}	else {
					
							$a.="	<td align=center > 2222".$intervalo->days." </td>";
				
						}
						
					}	else {
					
						$a.="	<td align=center > 11111 $arrpes[tjdata] </td>";
				
					}	
						*/
				//}
				
				
				// a protege pesquisa só tj
				if ( $_GET['contaprincipal'] != 920854) { 
				// consultoria
					
					$enviarconsultoria = botaoconsultoria($_GET['codipessoa'],$arr['tipovinculo'],$arr['contaprincipal'],$arr['conta']) ;			

					if ( $arrpes['ckconsultoria'] == 't')
						$corbotao = 'success';
					else
						$corbotao = 'danger';
					
					if ($arrpes['consultoriadata'] == date('d/m/Y')) 
						$a.="<td align=center><div id='divconsultoria'> <a href='#ancoramotobs' class='btn btn-$corbotao btn-sm  mb-1' onclick=fun_obs_consultoria_motorista('$_GET[codipessoa]','$arr[protocolo]')>    <img src='../0bmp/$arrpes[ckconsultoria].png' align='absmiddle'  width='40' height='40'>  Consultoria <br> Agora <br> $arrpes[consultoriausuario]  </a></div></td>";
					else{
						$a.="<td align=center><div id='divconsultoria'> <a href='#ancoramotobs' class='btn btn-light btn-sm  mb-1' onclick=fun_obs_consultoria_motorista('$_GET[codipessoa]','$arr[protocolo]')>    <img src='../0bmp/$arrpes[ckconsultoria]a.png' align='absmiddle' width='40' height='40'> Consultoria <br>$arrpes[consultoriadata] <br>   $arrpes[consultoriausuario] </a> <br> $enviarconsultoria </div></td>";								
					}
					
					//receita
					if ( $arrpes['ckreceita'] == 't')
						$corbotao = 'success';
					else
						$corbotao = 'danger';
					
					if ($arrpes['receitadata'] == date('d/m/Y') ) 
						$a.="	 	<td align=center><div id='divreceita'>  <a href='#ancoramotobs' class='btn btn-$corbotao btn-sm  mb-1' onclick=fun_obs_receita_motorista('$_GET[codipessoa]','$arr[protocolo]')>  <img src='../0bmp/$arrpes[ckreceita].png' align='absmiddle' border='0' width='40' height='40'>  Receita <br> Agora <br> $arrpes[receitausuario] </a></div> </td>";
					else
						$a.="	 	<td align=center><div id='divreceita'> <a href='#ancoramotobs' class='btn btn-light btn-sm  mb-1'  onclick=fun_obs_receita_motorista('$_GET[codipessoa]','$arr[protocolo]')>  <img src='../0bmp/$arrpes[ckreceita]a.png' align='absmiddle' border='0' width='40' height='40'>   Receita <br> $arrpes[receitadata] <br> $arrpes[receitausuario] </a></div></td>";
				
				
	//echo "<br> fazer referencia $arr[fazerreferencia] <br>";
						
					//fonemot
					if ($fazerreferencia == 't') {	
					
						if  (  $arr['tipovinculo'] == 'FUNCIONARIO') {
													
							$arrpes['ckfone'] = 't';
							$arrplacacarro['ckfone']  = 't';
							$arrplacareboque['ckfone']  = 't';
							$arrplacasemireboque['ckfone']  = 't';
							$arrplacaterceiroreboque['ckfone']  = 't';
																	
						} else {	
							
							//if  ( $arr[tipovinculo] != 'AGREGADO')   {	
							if ( $arrpes['ckfone'] == 't')
								$corbotao = 'success';
							else
								$corbotao = 'danger';
							
		
							if ($arrpes['fonedata'] == date('d/m/Y') ) 
								$a.="	 	<td align=center ><div id='divfonemot'>     <a href='#ancoramotobs' class='btn btn-$corbotao btn-sm mb-1'  onclick=fun_obs_fonemotorista_motorista('$_GET[codipessoa]','$arr[protocolo]')> <img src='../0bmp/$arrpes[ckfone].png' align='absmiddle' border='0' width='40' height='40'>         Fone <br> Agora <br> $arrpes[foneusuario] </a> </div></td>";
							else	
								$a.="	 	<td align=center ><div id='divfonemot'>     <a href='#ancoramotobs' class='btn btn-light btn-sm mb-1'  onclick=fun_obs_fonemotorista_motorista('$_GET[codipessoa]','$arr[protocolo]')> <img src='../0bmp/$arrpes[ckfone]a.png' align='absmiddle' border='0' width='40' height='40'>         Fone <br> $arrpes[fonedata] <br> $arrpes[foneusuario] </a></div> </td>";
						
						}
					}	

					//verificar reconhecimento facial
					if ($arr['obrigabiometria'] == 't') { 
						
						echo "<h1>$_SESSION[obrigabiometria]</h1>";
						
						if ( $arrpes['ckfacial'] == 't')
								$corbotao = 'success';
							else
								$corbotao = 'danger';
							
						
						if ($arrpes['facialdata'] == date('d/m/Y') ) 
							$a.="	 	<td align=center ><div id='divfacial'>    <a href='#ancoramotobs' class='btn btn-$corbotao btn-sm mb-1' onclick=fun_obs_facial_motorista('$_GET[codipessoa]','$arr[protocolo]')><img src='../0bmp/$arrpes[ckfacial].png' align='absmiddle' border='0' width='40' height='40'> Biometria <br>Agora <br> $arrpes[facialusuario] </a></div> </td>";
						else
							$a.="	 	<td align=center ><div id='divfacial'>     <a href='#ancoramotobs' class='btn btn-light btn-sm mb-1' onclick=fun_obs_facial_motorista('$_GET[codipessoa]','$arr[protocolo]')><img src='../0bmp/$arrpes[ckfacial]a.png' align='absmiddle' border='0' width='40' height='40'>   Biometria <br>$arrpes[facialdata] <br> $arrpes[facialusuario]  </a></div>  </td>";
								
					}
									
					//serasa
					if ($limiteserasa  < 49000) {
											
						if ($arrpes['serasadata'] == date('d/m/Y') ) 
							$a.="	 	<td align=center><div id='divserasa'><a href='#ancoramotobs' class='btn btn-light btn-sm mb-1' onclick=fun_obs_serasa_motorista('$_GET[codipessoa]','$arr[protocolo]')><img src='../0bmp/$arrpes[ckserasa].png' align='absmiddle' border='0' width='40' height='40'>               </div> Serasa <b> ($limiteserasa) max. </a></b>  <br> $arrpes[totalserasa] Agora <br> $arrpes[serasausuario] <br>";
						else{
							$a.="	 	<td align=center><div id='divserasa'><a href='#ancoramotobs' class='btn btn-light btn-sm mb-1' onclick=fun_obs_serasa_motorista('$_GET[codipessoa]','$arr[protocolo]')><img src='../0bmp/$arrpes[ckserasa]a.png' align='absmiddle' border='0' width='40' height='40'>               </div> Serasa <b> ($limiteserasa) max. </a> <br> $arrpes[totalserasa] $arrpes[serasadata] <br> $arrpes[serasausuario] <br><a class='btn btn-info btn-sm' href='#ancoraplacareboque' role='button' onclick=\"window.open('formularioserasa.php?&cpfcnpj=$arrpes[cpfcnpj]&codipessoa=$arrpes[codipessoa]&uf=$arrpes[uf]&nomepessoa=$arrpes[nomepessoa]&contaprincipal=48813&conta=48813','','width=1200,height=800')\">Consultar Serasa </a>";

						}					
						
					}						

					//cnh
					
					if ( $arrpes['ckcnh'] == 't')
						$corbotao = 'success';
					else
						$corbotao = 'danger';
					
					if ($arrpes['cnhdata'] == date('d/m/Y') ) 
						$a.="	 	<td align=center ><div id='divcnh'>  <a href='#ancoramotobs' class='btn btn-$corbotao btn-sm mb-1' onclick=fun_obs_cnh_motorista('$_GET[codipessoa]','$arr[protocolo]')><img src='../0bmp/$arrpes[ckcnh].png' align='absmiddle' border='0' width='40' height='40'>  CNH pontos $arrpes[totalpontoscnh] <br> Agora <br> $arrpes[cnhusuario] </a></div></td>";
					else
						$a.="	 	<td align=center><div id='divcnh'>  <a href='#ancoramotobs' class='btn btn-light btn-sm mb-1' onclick=fun_obs_cnh_motorista('$_GET[codipessoa]','$arr[protocolo]')><img src='../0bmp/$arrpes[ckcnh]a.png' align='absmiddle' border='0' width='40' height='40'>  CNH pontos $arrpes[totalpontoscnh] <br> $arrpes[cnhdata] <br> $arrpes[cnhusuario]</a> </div></td>";
			

					

			
					// experiencia  bloqueia primeiro carregamento (bloqueia spot)										

					//botoes bolqueia experienca e rdo					
					if (($arr['bloqueiaspot'] == 'SIM') && ($fazerexperiencia == 't' )) {
												
						if ( $arrpes['experienciack'] == 't')
							$corbotao = 'success';
						else
							$corbotao = 'danger';
						
						if ($arrpes['experienciadata'] == date('d/m/Y') ) 
							$a.="	<td align=center><div id='divexperienciamotorista'>         <a href='#ancoramotobs' class='btn btn-$corbotao btn-sm mb-1' onclick=fun_obs_experiencia_motorista('$_GET[codipessoa]','$arr[protocolo]')><img src='../0bmp/$arrpes[experienciack].png' align='absmiddle' border='0' width='40' height='40'> Experiencia <br>Agora<br> $arrpes[experienciausuario] </a></div></td>";
						else
							$a.="	<td align=center><div id='divexperienciamotorista'>         <a href='#ancoramotobs' class='btn btn-light btn-sm mb-1' onclick=fun_obs_experiencia_motorista('$_GET[codipessoa]','$arr[protocolo]')><img src='../0bmp/$arrpes[experienciack]a.png' align='absmiddle' border='0' width='40' height='40'>  Experiencia <br> $arrpes[experienciadata] <br> $arrpes[experienciausuario] </a></div></td>";
												
					}

					//botao para bloquear e liberar rdo
					if (($arr['rdoautomatico'] == 't') && ($fazerrdo == 't' )  ) {	
					
						if ( $arrpes['ckrdo'] == 't')
							$corbotao = 'success';
						else
							$corbotao = 'danger';
						

						if ($arrpes['rdodata'] == date('d/m/Y') ) 
							$a.="	<td align=center ><div id='divrdomotorista'> <a href='#ancoramotobs'  class='btn btn-$corbotao btn-sm mb-1' onclick=fun_obs_rdo_motorista('$_GET[codipessoa]','$arr[protocolo]')><img src='../0bmp/$arrpes[ckrdo].png' align='absmiddle' border='0' width='40' height='40'>  Rdo <br> Agora <br> $arrpes[rdousuario]</a>    </div></td>";
						else
							$a.="	<td align=center><div id='divrdomotorista'> <a href='#ancoramotobs' class='btn btn-light btn-sm mb-1' onclick=fun_obs_rdo_motorista('$_GET[codipessoa]','$arr[protocolo]')><img src='../0bmp/$arrpes[ckrdo]a.png' align='absmiddle' border='0' width='40' height='40'>  Rdo <br> $arrpes[rdodata] <br> $arrpes[rdousuario]</a>    </div></td>";
												
					}
				}	
						
				$a.="</tr>";

				
			$a .="</table>	";
												
			//*********************************************************
			//* validacao do motorista 
			//*********************************************************
			
			// esta parte mostra a validacao do motorista
			// aqui mostra os dados da ultima pesqusa data 			
			// primeiro tem que verificar se existe algum registro para o codipessoa

//			$a .="<br><table class='tabla_cabecera' border='0'  align='cnter' width='100%'>";
						
			    // carrega qtdcheque e vigencia, dependendo do tipo do motoristas
			
			
				if ( trim($arrpes['pessoack'] == '') ){
			  
					$sqlnew = " 
						insert into tvalidapessoa(codipessoa,obs)
						values('$_GET[codipessoa]','$_SESSION[usuario];')";
					
					$resnew = pg_exec($sqlnew);
									
				}			
			
//			$a .="</table>";	
						
			//***********************************************************
			//* mostra as referencias
			//***********************************************************
				
				
			//se é para fazer referencia mostra botoes
			if ( $fazerreferencia == 't')   { 	
					
				if  (  $arr['tipovinculo'] != 'FUNCIONARIO') {
						
					//if  ( $arr[tipovinculo] != 'AGREGADO')   {	
		
				
						$sqlref = "
							select
								nome,
								fone,
								cidade,
								uf,
								contato,
								ckdata,
								obs,
								ck,
								treferencia.codireferencia as codireferencia,
								to_char((current_timestamp - ckdata), 'DD') as pesquisadodiasatraz
							from treferencia,
								treferenciapessoa
							where codipessoa = '$_GET[codipessoa]' and
								treferencia.codireferencia = treferenciapessoa.codireferencia
								order by codireferencia desc ";		
							
						$respref = pg_exec($sqlref);
						
						if ( pg_numrows($respref) > 0 ){

							$a .="<br> <table class='tabla_cabecera' border='0' align=center width='100%'>";
							$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=4><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'> Referencia(s) </td></tr>";

							for ($i=0; $i < pg_numrows($respref ); $i++) {
			 
								$arrref=pg_fetch_array($respref,$i,PGSQL_ASSOC);

								if ( ($arrref['ck'] != 't') && (  $arr['tipovinculo'] != 'FUNCIONARIO') ) {
									
									$posmot = 'f';			
	
//echo " <br> arrref[ck]  $arrref[ck] ";
//echo " <br> 11	posmot  	$posmot  ";
	
								}
						
						//		$a .="<tr><td colspan=4>$arrref[fone] ( $arrref[nome] ) $arrref[contato] $arrref[cidade] - $arrref[uf]</td>";
													
								$a.="<a name='ancoraref$i' id='ancoraref$i'></a>";	
								 
								//$auxobs = str_replace(";","<br>",$arrref['obs']);
								
								$auxobs = str_replace(";","\n",$arrref['obs']);
								

								$a .="<tr class='botonmoduleon'>";
								$a .="		<td align=center rowspan=3 width='10%'><div id='div$i'><a href='#ancoraref$i' onclick=fun_obs_ref('$arrref[codireferencia]','$i','$arrref[fone]','$arr[protocolo]')> <img src='../0bmp/$arrref[ck]a.png' align='absmiddle' border='0' width='25' height='25'></a></div> $arrref[ckdata] <br> $arrref[pesquisadodiasatraz] dias</td>";
								$a .="		<td colspan=2>  <b>Referencia ".($i+1)." - $arrref[fone] ( $arrref[nome] ) $arrref[contato] $arrref[cidade] - $arrref[uf] </b> ";
								$a .="</tr><tr class='botonmoduleon'>";

								$a .="		  <td><textarea readonly COLS=100 ROWS=2> $auxobs </textarea> </td> <td><a href='../-/interrisco/alterarespostapesquisa.php?&tipo=referencia&codireferencia=$arrref[codireferencia]&obs=$arrref[obs]' TARGET = '_blank' ><div id='buttonaz'><img src='../0bmp/interno.gif' width='20' height='20'  border='0' align='absmiddle'> Edita</DIV></a></td>";
								$a .="</tr><tr class='botonmoduleon'>";
								$a .="					<td colspan=2><div id='div_ref_opcoes$i'>  </div> <div id='div_retorno_obs_ref$i'></div></td></tr>";
						
					
							}
								
							$a .="</table>";

						}
					//}
				}	
			}	
			
		}else{
		
			// se nao tem motorista, pega a tolerancia c  heque para veiculo
		  
			$sqltolcheque = "
				select			  
					qtdchequefuncionario,
					qtdchequeagregado,
					qtdchequeautonomo,
					vigenciafuncionario,
					vigenciaagregado,
					tparametrocadastro.custopesquisa,
					vigenciaautonomo
				from
					tparametrocadastro
			
				where	tparametrocadastro.contaprincipal = $_GET[contaprincipal] and
					tparametrocadastro.conta = $_GET[conta] ";

			$restolcheque = pg_exec($sqltolcheque);
		
			if ( pg_numrows($restolcheque) > 0 ){

				$arrtolcheque = pg_fetch_array($restolcheque,0,PGSQL_ASSOC);
				  		  
				$toleranciachque= '';
				
				if (trim($arr['tipovinculo']) == 'FUNCIONARIO' || $arr['tipovinculo'] == 'INTERNO' ) {
					$toleranciachque = $arrtolcheque['qtdchequefuncionario'];
					$vigencia = $arrtolcheque['vigenciafuncionario'];					
				} else if ($arr['tipovinculo'] == 'AGREGADO'  ||  $arr['tipovinculo'] == 'PROPRIETARIO') {
					$toleranciachque = $arrtolcheque['qtdchequeagregado'];
					$vigencia = $arrtolcheque['vigenciaagregado'];
				} else if ($arr['tipovinculo'] == 'AUTONOMO' || $arr['tipovinculo'] == 'AJUDANTE' ) {
					$toleranciachque = $arrtolcheque['qtdchequeautonomo'];
					$vigencia = $arrtolcheque['vigenciaautonomo'];
				}	
				
				$custopesquisa =  str_replace("R$ ","",$arr['custopesquisa']);
				
			}					
		
		}
		
		//****************************************
		//* placacarro
		//****************************************
		
		if (strlen($_GET['placacarro']) > 0 ) {
			
			// zera a variavel de avisos senao vai mostrar o mesmo do motorista
			$avisos = '';	
			
			$sqlplacacarro = "			
				select 
					tcarro.placa as placa,
					tcarro.codipessoa as codipessoa,
					tcarro.ufplaca,
					tcarro.renavan,
					tcarro.chassi,
					tcarro.anofabricacao,
					tcarro.cor,
					tcarro.categoria,
					tcarro.marca,
					tcarro.modelo,
					tcarro.antt,
					tcarro.detrancpfcnpj,
					tcarro.detrannomeproprietario,						
					tpessoa.nomepessoa,
					tpessoa.cep,
					tpessoa.cidade,
					tpessoa.uf,
					tpessoa.endereco,					
					tpessoa.fone,
					tpessoa.celular,
					tpessoa.tipopessoa,
					tpessoa.cpfcnpj,
					tcarro.numeroseguranca,					
					to_char((current_timestamp - ckdata), 'DD') as pesquisadodiasatraz,
					tvalidaplaca.ckdata,
					tvalidaplaca.cklicenciamento,					
					tvalidaplaca.placa as validaplaca_placa,
					tvalidaplaca.ckpropriedade,
					tvalidaplaca.obs,
					tvalidaplaca.ckcheque,					
					tvalidaplaca.ckfone,
					tvalidaplaca.ckantt,
					tvalidaplaca.ckreceita,
					tvalidaplaca.ck,
					tvalidaplaca.receitadata,
					tvalidaplaca.receitausuario,
					tvalidaplaca.anttdata,
					tvalidaplaca.anttusuario,
					tvalidaplaca.chequedata,
					tvalidaplaca.chequeusuario,
					tvalidaplaca.fonedata,
					tvalidaplaca.foneusuario,
					tvalidaplaca.propriedadedata,
					tvalidaplaca.propriedadeusuario,
					tvalidaplaca.licenciamentodata,
					tvalidaplaca.licenciamentousuario,
					tvalidaplaca.experienciadata,
					tvalidaplaca.experienciausuario,
					tvalidaplaca.experienciack,	
					tantt.datacriacao,
					tdoc.tipodoc,
					tdoc.quantidade,
					tdoc.extensao,
					tdoc.copiadocurl,
					tcorrectdata.data as correctdata,
					tcorrectdata.usuario as correctusuario, 
					tcorrectdata.obs as correctobs,
					to_char((current_timestamp -tdoc.dataentrada), 'DD') as tdocdataentradadias,
					to_char(tdoc.dataentrada, 'DD/MM/YY HH24:MI') as tdocdataentrada
					
				from
					tpessoa,
					tcarro 	LEFT OUTER JOIN tvalidaplaca ON (tcarro.placa = tvalidaplaca.placa)
							LEFT OUTER JOIN tdoc ON (tcarro.placa = tdoc.placa) 
							LEFT OUTER JOIN tantt ON (tcarro.antt = tantt.antt)
							LEFT OUTER JOIN tcorrectdata ON (tcarro.placa = tcorrectdata.placa)
							
				where tcarro.placa = '$_GET[placacarro]'  and
					tcarro.codipessoa = tpessoa.codipessoa ";
 
			$resplacacarro = pg_exec($sqlplacacarro);
											

			if ( pg_numrows($resplacacarro) > 0 ){

				$arrplacacarro = pg_fetch_array($resplacacarro,0,PGSQL_ASSOC);
				
				// aqui eu verifico se tem algum registro em tvalidaplaca
				// se nao tiver eu faco um insert senao vai dar ploblema nos updates abaixo
				
				if ( trim($arrplacacarro['validaplaca_placa']) == '' ){
			  
					$sqlnew = " 
						insert into tvalidaplaca(placa,obs)
						values('$arrplacacarro[placa]','$_SESSION[usuario]')";
					
					$resnew = pg_exec($sqlnew);
								
				}	
						
				
					//if ( (   $arr['fazercheque'] != 	'f' )   ) 	 {				
					//	if ($arrplacacarro['ckcheque'] != 't')
					//		$poscarro = 'f';			
					//}
		
					if ($arrplacacarro['ckpropriedade'] != 't' ) 
						$poscarro = 'f';			
										
					if ($arrplacacarro['tipopessoa'] == 'CNPJ') {
						if ($arrplacacarro['ckreceita'] != 't' ) 
							$poscarro = 'f';
					}		
															
					//se nao precisar pesquisar antt nao negative
					if ($arr['parametroantt'] != 'NAO')
						if ($arrplacacarro['ckantt'] != 't' ) 
							$poscarro = 'f';			
										
					if ($arrplacacarro['cklicenciamento'] != 't' ) {
						$poscarro = 'f';			
					}


				//*****************************************
				//* verifico ano fabricacao do veiculo (automatico)
				//*****************************************
				
				//2023 - 2013 > 10
				//2023 - 2020 > 10
				//2023 - 2000 > 10
				
				
				//se o ano atual menos a datade fabricacao = idade do veiculo  for maior que a idade maxima 
				if ( ( (int)date('Y')  - (int)$arrplacacarro['anofabricacao'] )  >  (int)$arr['idademaximacarro']    ) {
				
					$idademaximacarro = "<table><tr class='redonda'><td align=center> <img src='../0bmp/a.png' align='absmiddle' border='0' width='25' height='25'> ATENCAO.:  Carro com mais de $arr[idademaximacarro] anos de fabricacao, NAO PODE SER LIBERADO !!!<td></tr></table>";
					$poscarro = 'f';	
					

					// sistema bloqueia automatico idade maxima Veiculo
					// logica: se ano fabricacao ainda nao existe ele executa a gravacao negativa em tvalida e tchamada
					// se ja fez essa validacao (atravez do strpos) dai nao grava para nao ficar duplicando respostas
					$posicao = strpos($arrplacacarro['obs'], ' Ano fabricacao ');

					if ($posicao == false) {
											
						$sqla = "
							update tvalidaplaca
							set obs  = ('(".date('dmy')." $_SESSION[usuario]) Ano fabricacao $arrplacacarro[placa]  maior que idade maxima $arr[idademaximacarro] Anos;' || obs)
							where placa = '$arrplacacarro[placa]'  ";
									
						$res = pg_exec($sqla);									
					
						$sqlchamadaw = "
							update tchamada
							set resposta = ('* Ano fabricacao $arrplacacarro[placa]  maior que idade maxima $arr[idademaximacarro] Anos;' || resposta)
							where protocolo = '$_GET[protocolo]'  ";
								
						$res = pg_exec($sqlchamadaw);
						
					
					}
					//stripos
					//Neste exemplo, a função strpos() procura a substring "exemplo" dentro da string "$frase". Se a substring for encontrada, ela retorna a posição da primeira ocorrência da substring na string. Se a substring não for encontrada, ela retorna false. Portanto, verificamos se $posicao não é igual a false antes de imprimir a posição onde a substring foi encontrada.
					//Lembre-se de que strpos() é sensível a maiúsculas e minúsculas. Se você quiser fazer uma pesquisa que não seja sensível a maiúsculas e minúsculas, pode usar a função stripos() em vez disso.

					
					
										
				}				

						
			
				
				$a .="<table class='tabla_cabecera'  align=center width='100%' >";
							
				if ($arrplacacarro['detrancpfcnpj'] == '') {	
				
					$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=6><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'>VEICULO: &nbsp;&nbsp; ($arrplacacarro[placa])  UF $arrplacacarro[ufplaca] &nbsp;&nbsp; Renavan: $arrplacacarro[renavan]- ANTT $arrplacacarro[antt] - Data Criacao $arrplacacarro[datacriacao] </td></tr>";
					$a .="<tr><td> Cpf/Cnpj </td><td> $arrplacacarro[cpfcnpj]</td>";
					$a .="	<td> Nome </td><td> $arrplacacarro[nomepessoa] </td></tr>";
					
				}else{
					//	detrancpfcnpj   detrannomeproprietario	 	
					$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=6><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'>VEICULO: &nbsp;&nbsp; ($arrplacacarro[placa])  UF $arrplacacarro[ufplaca] &nbsp;&nbsp; Renavan: $arrplacacarro[renavan]- ANTT $arrplacacarro[antt] - Data Criacao $arrplacacarro[datacriacao] </td></tr>";
					
					$a .="<tr  class='table-info'><td> Cpf/Cnpj Antt </td><td> $arrplacacarro[cpfcnpj]</td>";
					$a .="	<td> Nome Antt</td><td> $arrplacacarro[nomepessoa] </td></tr>";			
					$a .="<tr  class='table-success'><td> Cpf/Cnpj Detran </td><td> $arrplacacarro[detrancpfcnpj]</td>";
					$a .="	<td> Nome Detran</td><td> $arrplacacarro[detrannomeproprietario] </td></tr>";
								
				}

				$a .="<tr><td> Endereco </td><td> $arrplacacarro[endereco]</td>";
				$a .="	<td> Cidade </td><td> $arrplacacarro[cidade] - $arrplacacarro[uf]</td>";				
				$a .="<tr><td> Fone </td><td> $arrplacacarro[fone]</td>";     
				$a .="	<td> Celular </td><td> $arrplacacarro[celular] N. Seguranca: $arrplacacarro[numeroseguranca]</td>	</tr>";   

				$a .="<tr><td>  Categoria  </td><td> $arrplacacarro[categoria] </td>";
				$a .="	<td> Ano Fabr </td><td>$arrplacacarro[anofabricacao]</td>		 ";
				$a .="<tr><td> Marca </td><td>$arrplacacarro[marca]</td>";
				$a .="	<td> Modelo </td><td>$arrplacacarro[modelo]</td></tr>		 ";
				$a .="<tr><td> Chassi </td><td>$arrplacacarro[chassi]</td>";
				$a .="	<td> Cor </td><td> $arrplacacarro[cor]</td></tr>";
								
				//* pega os arquivos de upload *
											
				if ( $arrplacacarro['copiadocurl'] != '' || $arrplacacarro['tipodoc'] != '') {				
					//funcao declarada aqui
					$a .= "	<table width=100%>
								<tr><td><div class='alert alert-borda' style='border-color: blue;' role='alert'> Anexo Veiculo(s): ".criabotaoanexo($resplacacarro,'placa')."</div></td></tr>
							</table>";
				}
				
				
				
				
				//	$a .="</table><table align=center width='100%' >";
									
				//******************************************
				// verifica se tem ocorrencia para o veiculo
				//******************************************
				$sqlpesocorrencia = "
					select obs,
						to_char(datacriacao, 'DD/MM/YY') as datacriacao,
						usuario
					from tocorrencia
					where trim(chavedebusca) = '$arrplacacarro[placa]'	
					order by codiocorrencia ";
			
				$ressqlopescorrencia = pg_exec($sqlpesocorrencia);	
														
				if ( pg_numrows($ressqlopescorrencia) > 0 ){
				
					$avisos .= "							
						<div class='alert alert-borda' style='border-color: yellow;' role='alert'>
							<img src='../0bmp/a.png'  width='30' height='30' border=0 > AVISO  - Este motorista tem ALERTAS <br>
						";
										
					$avisos .= "<textarea readonly COLS=90 ROWS=1>";
					
					for ($i=0; $i < pg_numrows($ressqlopescorrencia ); $i++) {

						$arrpesocorrencia = pg_fetch_array($ressqlopescorrencia,$i,PGSQL_ASSOC);
						$avisos .= "$arrpesocorrencia[datacriacao] $arrpesocorrencia[usuario] - $arrpesocorrencia[obs] \n";
					}
					
					$avisos .= "</textarea> </div>";
					
				}

				//******************************
				//*  verifica alerta blacklist
				//*******************************
				
				$msgalerta = puxablacklistcarro($arrplacacarro['placa'],''); 
				
				if ( $msgalerta <> '') {
					
					$avisos .= $msgalerta;
					
					
				}	
										
				// verifico se o veiculo esta cadastrado fora do estado de residencia do
				// motorista

				if ($arrplacacarro['uf'] !=  $arrplacacarro['ufplaca']) {
				
					$avisos = "<br><img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle'> ATENCAO ! ALERTA Divergencia de UF <BR>";
					$avisos .= "<textarea readonly COLS=90 ROWS=1>";							
					$avisos .= "O proprietaro do veiculo possui residencia na UF $arrplacacarro[uf] e o veiculo foi registrado no Detran de $arrplacacarro[ufplaca] \n";
					$avisos .= "</textarea>";
				
				}		
				
				//*****************************
				//* tela de consulta de veiculo
				//*****************************

				//tira a ;e insere <br> para quebrar linha no html
				$auxobs = str_replace(";","<br>",$arrplacacarro['obs']);
				
				// cria uma ancora aqui
				$a.="<a name='ancoraplacacarro' id='ancoraplacacarro'></a>	</table>";
					
					
					
/*					
				$a .="<table align=center width='100%'  >";
				$a .="<tr class='botonmoduleon'><td> $idademaximacarro $avisos </td><td><a href='../0ocorrencia/criaocorrencia.php?chavedebusca=$_GET[placacarro]&criterioporget=PLACA' > <div id='buttonaz'><img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle' target = '_blank' > Criar Alerta</div></a></td><tr>";
				$a .="<tr class='botonmoduleon'><td> $auxobs </td><td><a href='../-/interrisco/alterarespostapesquisa.php?&tipo=placa&placa=$arrplacacarro[placa]&obs=$arrplacacarro[obs]' TARGET = '_blank' ><div id='buttonaz'><img src='../0bmp/interno.gif' width='15' height='15'  border='0' align='absmiddle'> Edita</DIV></a></td><tr>";
				$a .="<tr class='botonmoduleon'><td colspan=2> <div id='div_obs_placacarro'></div> <div id='div_resp_gravaobs_placacarro'></div></td><tr>";
				$a .="</table>";
					
*/



			$a .="<table width='100%' cellpadding='3' > ";
			$a .="<tr><td><div role='alert'> $idademaximacarro  </div>	</td><td rowspan=5> <a href='#' onclick=historicocarregamentos('$arrpes[cpfcnpj]'); class='btn btn-success btn-sm mb-2'>  																		<img src='../0bmp/prancheta.jpg' width='20' height='20' align='absmiddle'  > 											Ver os Historicos</div></a><br>
															<a href='../0ocorrencia/criaocorrencia.php?chavedebusca=$_GET[placacarro]&criterioporget=PLACA' target = '_blank' class='btn btn-success btn-sm mb-2' > 				<img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle' target = '_blank' > 				Gera novo Alerta </a><br>
															<a href='../-/interrisco/alterarespostapesquisa.php?&tipo=placa&placa=$arrplacacarro[placa]&obs=$arrplacacarro[obs]' target = '_blank' class='btn btn-success btn-sm mb-2'>	<img src='../0bmp/interno.gif' width='15' height='15'  border='0' align='absmiddle'> 									Editar mensagens </a><br>
															<a href='#' onclick=pesquisa('$arrpes[cpfcnpj]','$_GET[grupo]','$_GET[protocolo]','$_GET[codipessoa]','$_GET[placacarro]','$_GET[placareboque]','$_GET[placasemireboque]','$_GET[placaterceiroreboque]','$_GET[conta]','$_GET[contaprincipal]','consulta','$_GET[enviaemailresposta]') class='btn btn-success btn-sm mb-2' > 	Atualizar Pagina (F5)</a>															
															</td></tr>";			
			$a .="<tr><td> $avisos 		</td></tr>";
			$a .="<tr><td> 	$auxobs 	</td></tr>";			
			$a .="<tr><td>  <div id='div_obs_placacarro'></div></td></tr>";
			$a .="<tr><td>  <div id='div_resp_gravaobs_placacarro'></div> </td></tr>";
			$a .="</table>";
			


					

				//botao do veiculo
							
				$a.="<table  bgcolor=#DCDCDC><tr>	 ";
									
				if ($arrplacacarro['ck'] == '') 
					$a.="	<td align=center><a href='pesquisas.php'  class='btn btn-light btn-sm mb-1'  target='_blank' ><img src='../0bmp/$arrplacacarro[ck]a.png' align='absmiddle' border='0' width='20' height='20'>  <br> Agora <br> $arrplacacarro[pesquisadodiasatraz] dias </a> ";
				else
					$a.="	<td align=center><a href='pesquisas.php'  class='btn btn-light btn-sm mb-1'  target='_blank' ><img src='../0bmp/$arrplacacarro[ck].png' align='absmiddle' border='0' width='20' height='20'>  <br> $arrplacacarro[ckdata] <br> $arrplacacarro[pesquisadodiasatraz] dias </a> ";
						
				
				// SE FOR CPF NAO PRECISA FICAR COLOCANDO RECEITA FEDERAL
				if ($arrplacacarro['tipopessoa'] == 'CNPJ') {
					//RECEITA	
					
					
					if ( $arrplacacarro['ckreceita'] == 't')
						$corbotao = 'success';
					else
						$corbotao = 'danger';
					
					
					if ($arrplacacarro['receitadata'] == date('d/m/Y') ) 
						$a.="	<td align=center ><div id='divplacacarroreceita'><a href='#ancoraplacacarro' class='btn btn-$corbotao btn-sm mb-1' onclick=fun_obs_placareceita('$arrplacacarro[placa]','$arr[protocolo]','carro')>       <img src='../0bmp/$arrplacacarro[ckreceita].png' align='absmiddle' border='0' width='20' height='20'>   R. Fazenda  <br>Agora <br>$arrplacacarro[receitausuario]</div></td>";
					else
						$a.="	<td align=center ><div id='divplacacarroreceita'><a href='#ancoraplacacarro' class='btn btn-light btn-sm mb-1' onclick=fun_obs_placareceita('$arrplacacarro[placa]','$arr[protocolo]','carro')>       <img src='../0bmp/$arrplacacarro[ckreceita]a.png' align='absmiddle' border='0' width='20' height='20'>   R. Fazenda <br>$arrplacacarro[receitadata] <br>$arrplacacarro[receitausuario]</a></div></td>";
				}
										
				//if ( (   $arr['fazercheque'] != 	'f' )   ) 	 {				
					//cheque	
				//	if ($arrplacacarro['chequedata'] == date('d/m/Y') ) 
				//		$a.="	<td align=center  bgcolor=#FFFFF0><div id='divplacacarrocheque'><a href='#ancoraplacacarro' onclick=fun_obs_placacheque('$arrplacacarro[placa]','$arr[protocolo]','carro')>        <img src='../0bmp/$arrplacacarro[ckcheque].png' align='absmiddle' border='0' width='20' height='20'>        </div> Cheque <b> ($toleranciachque ) max. </b></a> <br>$arrplacacarro[chequedata] <br>$arrplacacarro[chequeusuario]  <br> <a class='btn btn-info btn-sm' href='#ancoraplacacarro' role='button' onclick=\"window.open('serverxmlccf.php?&cpfcnpj=$arrplacacarro[cpfcnpj]&codipessoa=$arrplacacarro[codipessoa]','','width=800,height=500')\">Consutar Cheque</a></td>";
				//	else
							// se for servis nao precisa c	onsultar cheque
						
				//			$a.="	<td align=center class=redonda><div id='divplacacarrocheque'>       <a href='#ancoraplacacarro' onclick=fun_obs_placacheque('$arrplacacarro[placa]','$arr[protocolo]','carro')>        <img src='../0bmp/$arrplacacarro[ckcheque]a.png' align='absmiddle' border='0' width='20' height='20'>        </div> Cheque <b> ($toleranciachque ) max. </b></a> <br> <a class='btn btn-info btn-sm' href='#ancoraplacacarro' role='button' onclick=\"window.open('serverxmlccf.php?&cpfcnpj=$arrplacacarro[cpfcnpj]&codipessoa=$arrplacacarro[codipessoa]','','width=800,height=500')\">Consutar Cheque</a></td>";
				//}			
					
				//licenciamento	
				if ( $arrplacacarro['cklicenciamento'] == 't')
					$corbotao = 'success';
				else
					$corbotao = 'danger';
				
				if ($arrplacacarro['licenciamentodata'] == date('d/m/Y') ) 
					$a.="	<td align=center ><div id='divplacacarrolicenciamento'><a href='#ancoraplacacarro' class='btn btn-$corbotao btn-sm mb-1' onclick=fun_obs_placalicenciamento('$arrplacacarro[placa]','$arr[protocolo]','carro')> <img src='../0bmp/$arrplacacarro[cklicenciamento].png' align='absmiddle' border='0' width='20' height='20'>  Ipva/Lic <br>Agora <br>$arrplacacarro[licenciamentousuario]</a></div></td>";
				else
					$a.="	<td align=center ><div id='divplacacarrolicenciamento'><a href='#ancoraplacacarro' class='btn btn-light btn-sm mb-1' onclick=fun_obs_placalicenciamento('$arrplacacarro[placa]','$arr[protocolo]','carro')> <img src='../0bmp/$arrplacacarro[cklicenciamento]a.png' align='absmiddle' border='0' width='20' height='20'>  Ipva/Lic <br>$arrplacacarro[licenciamentodata] <br>$arrplacacarro[licenciamentousuario] </a></div></td>";

				//certificado de propriedade	
				if ( $arrplacacarro['ckpropriedade'] == 't')
					$corbotao = 'success';
				else
					$corbotao = 'danger';
				
				if ($arrplacacarro['propriedadedata'] == date('d/m/Y') ) 
					$a.="	<td align=center><div id='divplacacarropropriedade'>  <a href='#ancoraplacacarro' class='btn btn-$corbotao btn-sm mb-1' onclick=fun_obs_placapropriedade('$arrplacacarro[placa]','$arr[protocolo]','carro')>   <img src='../0bmp/$arrplacacarro[ckpropriedade].png' align='absmiddle' border='0' width='20' height='20'>    Cert. Prop. <br>Agora <br>$arrplacacarro[propriedadeusuario]</a></div></td>";
				else
					$a.="	<td align=center><div id='divplacacarropropriedade'>  <a href='#ancoraplacacarro' class='btn btn-light btn-sm mb-1' onclick=fun_obs_placapropriedade('$arrplacacarro[placa]','$arr[protocolo]','carro')>   <img src='../0bmp/$arrplacacarro[ckpropriedade]a.png' align='absmiddle' border='0' width='20' height='20'>   Cert. Prop. <br>$arrplacacarro[propriedadedata] <br>$arrplacacarro[propriedadeusuario]</a></div></td> ";


				//grava placa
				//corect data
				$a.="<td align=center><div id='divplacacarropropriedade'>  <a href='#ancoraplacacarro' class='btn btn-light btn-sm mb-1' onclick=\"window.open('correctdata.php?&placa=$arrplacacarro[placa]' ,'','width=1180,height=620,left=50,top=50');\">   Dados Placa <br> $arrplacacarro[correctdata] <br> $arrplacacarro[correctusuario] </a></div></td>";
								
				//se nao precisar pesquisar antt nao negative
				if ( $arrplacacarro['ckantt'] == 't')
					$corbotao = 'success';
				else
					$corbotao = 'danger';
				
				if ($arr['parametroantt'] != 'NAO') {				
				
					if ( $arrplacacarro['ckantt'] == 't')
						$corbotao = 'success';
					else
						$corbotao = 'danger';
					
				
					if ($arrplacacarro['anttdata'] == date('d/m/Y') ) 
						$a.="	<td align=center ><div id='divplacacarroantt'>         <a href='#ancoraplacacarro' class='btn btn-$corbotao btn-sm' onclick=fun_obs_placaantt('$arrplacacarro[placa]','$arr[protocolo]','carro')>          <img src='../0bmp/$arrplacacarro[ckantt].png' align='absmiddle' border='0' width='20' height='20'>           ANTT  <br>$Agora <br>$arrplacacarro[anttusuario]</a></div></td>";
					else
						$a.="	<td align=center ><div id='divplacacarroantt'>         <a href='#ancoraplacacarro' class='btn btn-light btn-sm' onclick=fun_obs_placaantt('$arrplacacarro[placa]','$arr[protocolo]','carro')>          <img src='../0bmp/$arrplacacarro[ckantt]a.png' align='absmiddle' border='0' width='20' height='20'>           ANTT <br>$arrplacacarro[anttdata] <br>$arrplacacarro[anttusuario]</a></div></td>";
					
					// se a antt é da data de hoje entao 
//						if ($arrplacacarro['anttdata'] == date('d/m/Y') ) 
						
//						else
//							$a.="	<td align=center class=redonda><div id='divplacacarroantt'>         <a href='#ancoraplacacarro' class='btn btn-success btn-sm' onclick=\"window.open('../-/antt/antttela.php' ,'','width=1180,height=620,left=50,top=50')>          <img src='../0bmp/$arrplacacarro[ckantt]a.png' align='absmiddle' border='0' width='20' height='20'>     </div> ANTT </a></td>";
					
					
					//-/tela.php
					//$a.=" <div class='btn btn-success btn-sm' onclick=\"window.open('../-/antt/antttela.php' ,'','width=1180,height=620,left=50,top=50')\"> Pesq Antt </div>";
					
				}
				
				//A FUNCAO PESQUISA (  TA EM  registrospendentes.php
				$a.="<td>	<a href='#ancoraplacacarro'  onclick=\"window.open('../-/antt/antttela.php' ,'','width=1180,height=620,left=50,top=50')\">   <img src='../0bmp/$arrplacacarro[ckantt].png' align='absmiddle' border='0' width='20' height='20'>    ANTT<br>$arrplacacarro[anttdata] </a>
							<a href='#ancoraplacacarro' class='btn btn-light btn-sm' onclick=pesquisa('$arrpes[cpfcnpj]','$_GET[grupo]','$_GET[protocolo]','$_GET[codipessoa]','$_GET[placacarro]','$_GET[placareboque]','$_GET[placasemireboque]','$_GET[placaterceiroreboque]','$_GET[conta]','$_GET[contaprincipal]','consulta','$_GET[enviaemailresposta]')><img src='../0bmp/atualiza.png' width='20' height='20'  border='0' align='absmiddle'>Atualiza (F5)</a></td></td></tr>";
				$a .="</table>	";
					
			}
		}

		//****************************************
		//* placareboque
		//****************************************
				
		if (strlen($_GET['placareboque']) > 0 ) {
	
			// zera a variavel de avisos senao vai mostrar o mesmo do motorista
			$avisos = '';	
	
			$sqlplacareboque = "			
				select 
					tcarro.placa as placa,
					tcarro.codipessoa as codipessoa,
					tcarro.ufplaca,
					tcarro.renavan,
					tcarro.chassi,
					tcarro.anofabricacao,
					tcarro.cor,
					tcarro.categoria,
					tcarro.marca,					
					tcarro.modelo,
					tcarro.detrancpfcnpj,
					tcarro.detrannomeproprietario,	
					
					tpessoa.nomepessoa,
					tpessoa.cep,
					tcarro.antt,
					tpessoa.cidade,
					tpessoa.uf,
					tpessoa.endereco,					
					tpessoa.fone,
					tpessoa.celular,
					tpessoa.tipopessoa,
					tpessoa.cpfcnpj,
					tcarro.numeroseguranca,					
					to_char((current_timestamp - ckdata), 'DD') as pesquisadodiasatraz,
					tvalidaplaca.ckdata,
					tvalidaplaca.cklicenciamento,	
					tvalidaplaca.ckantt,					
					tvalidaplaca.placa as validaplaca_placa,
					tvalidaplaca.ckpropriedade,
					tvalidaplaca.obs,
					tvalidaplaca.ckcheque,					
					tvalidaplaca.ckfone,
					tvalidaplaca.ckreceita,
					tvalidaplaca.ck,
					tvalidaplaca.receitadata,
					tvalidaplaca.receitausuario,
					tvalidaplaca.anttdata,
					tvalidaplaca.anttusuario,
					tvalidaplaca.chequedata,
					tvalidaplaca.chequeusuario,
					tvalidaplaca.fonedata,
					tvalidaplaca.foneusuario,
					tvalidaplaca.propriedadedata,
					tvalidaplaca.propriedadeusuario,
					tvalidaplaca.licenciamentodata,
					tvalidaplaca.licenciamentousuario,
					tvalidaplaca.experienciadata,
					tvalidaplaca.experienciausuario,
					tvalidaplaca.experienciack,
					tantt.datacriacao,
					tdoc.tipodoc,
					tdoc.quantidade,
					tdoc.extensao,
					tdoc.copiadocurl,
					tcorrectdata.data as correctdata,
					tcorrectdata.usuario as correctusuario, 
					tcorrectdata.obs as correctobs,
					
					to_char((current_timestamp - tdoc.dataentrada), 'DD') as tdocdataentradadias,
					to_char(tdoc.dataentrada, 'DD/MM/YY HH24:MI') as tdocdataentrada
					
				from
					tpessoa,
					tcarro 	LEFT OUTER JOIN tvalidaplaca ON (tcarro.placa = tvalidaplaca.placa)
							LEFT OUTER JOIN tdoc ON (tcarro.placa = tdoc.placa)
							LEFT OUTER JOIN tantt ON (tcarro.antt = tantt.antt)
							LEFT OUTER JOIN tcorrectdata ON (tcarro.placa = tcorrectdata.placa)
					
				where tcarro.placa = '$_GET[placareboque]'  and
					tcarro.codipessoa = tpessoa.codipessoa ";




			$resplacareboque = pg_exec($sqlplacareboque);
						
			if ( pg_numrows($resplacareboque) > 0 ){

				$arrplacareboque = pg_fetch_array($resplacareboque,0,PGSQL_ASSOC);
				
				// aqui eu verifico se tem algum registro em tvalidaplaca
				// se nao tiver eu faco um insert senao vai dar ploblema nos updates abaixo
				
				if ( trim($arrplacareboque['validaplaca_placa']) == '' ){
			  
					$sqlnew = " 
						insert into tvalidaplaca(placa,obs)
						values('$arrplacareboque[placa]','$_SESSION[usuario]')";
					
					$resnew = pg_exec($sqlnew);
								
				}	
			
			
						
				
					//if (    $arr['fazercheque'] != 	'f' )   	 {
					//	if ($arrplacareboque['ckcheque'] != 't') 
					//		$posreb = 'f';		
					//}	
					
					if ($arrplacareboque['ckpropriedade'] != 't' ) 
						$posreb = 'f';			
					
					if ($arrplacareboque['tipopessoa'] == 'CNPJ') {
						if ($arrplacareboque['ckreceita'] != 't' ) 
							$posreb = 'f';			
					}	
										
					if ($arrplacareboque['cklicenciamento'] != 't' ) 
						$posreb = 'f';			
					
					
					//se nao precisar pesquisar antt nao negative
					if ($arr['parametroantt'] != 'NAO')
						if ($arrplacareboque['ckantt'] != 't' ) 
							$posreb = 'f';			
					
					//if ($arrplacareboque['ckfone'] != 't' ) {
					//	$posreb = 'f';			
					//}

						
				
				
				

				//*****************************************
				//* verifica o ano de fabricacao do reb
				//*****************************************
				
				if ( ( (int)date('Y')  - (int)$arrplacareboque['anofabricacao'] )  >  (int)$arr['idademaximacarro']    ) {
				
				
					$idademaximareboque = "<table><tr class='redonda'><td align=center> <img src='../0bmp/a.png' align='absmiddle' border='0' width='25' height='25'> ATENCAO.:  reboque com mais de $arr[idademaximacarro] anos de fabricacao !!!<td></tr></table>";
					$posreb = 'f';	
					
					// sistema bloqueia automatico idade maxima Veiculo
					// logica: se ano fabricacao ainda nao existe ele executa a gravacao negativa em tvalida e tchamada
					// se ja fez essa validacao (atravez do strpos) dai nao grava para nao ficar duplicando respostas
					$posicao = strpos($arrplacareboque['obs'], ' Ano fabricacao ');

					if ($posicao == false) {
											
						$sqla = "
							update tvalidaplaca
							set obs  = ('(".date('dmy')." $_SESSION[usuario]) Ano fabricacao $arrplacareboque[placa]  maior que idade maxima $arr[idademaximacarro] Anos;' || obs)
							where placa = '$arrplacareboque[placa]'  ";
									
						$res = pg_exec($sqla);									
					
						$sqlchamadaw = "
							update tchamada
							set resposta = ('* Ano fabricacao $arrplacareboque[placa]  maior que idade maxima $arr[idademaximacarro] Anos;' || resposta)
							where protocolo = '$_GET[protocolo]'  ";
								
						$res = pg_exec($sqlchamadaw);
						
					
					}
					
				}


				//****************************
				//* pega os arquivos de upload
				//****************************
								
				//mostro os arquivos na tela
						
				if ( $arrplacareboque['copiadocurl'] != '' || $arrplacareboque['tipodoc'] != '') 				
					//funcao declarada aqui
					$a .= "<div class='alert alert-borda' style='border-color: blue;' role='alert'> Anexo(s): ".criabotaoanexo($resplacareboque,'placa')."</div>";
			
		
								
				
				$a .="<BR><table class='tabla_cabecera' border='0'  align=center width='100%' >";


				if ($arrplacareboque['detrancpfcnpj'] == '') {	
				
					$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=6><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'>REBOQUE: &nbsp;&nbsp; ($arrplacareboque[placa])  UF $arrplacareboque[ufplaca] &nbsp;&nbsp; Renavan: $arrplacareboque[renavan]- ANTT $arrplacareboque[antt] - Data Criacao $arrplacareboque[datacriacao] </td></tr>";
					$a .="<tr><td> Cpf/Cnpj </td><td> $arrplacareboque[cpfcnpj]</td>";
					$a .="	<td> Nome </td><td> $arrplacareboque[nomepessoa] </td></tr>";
					
				}else{
					//	detrancpfcnpj   detrannomeproprietario	 	
					$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=6><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'>REBOQUE: &nbsp;&nbsp; ($arrplacareboque[placa])  UF $arrplacareboque[ufplaca] &nbsp;&nbsp; Renavan: $arrplacareboque[renavan]- ANTT $arrplacareboque[antt] - Data Criacao $arrplacareboque[datacriacao] </td></tr>";
					
					$a .="<tr  class='table-info'><td> Cpf/Cnpj Antt </td><td> $arrplacareboque[cpfcnpj]</td>";
					$a .="	<td> Nome Antt</td><td> $arrplacareboque[nomepessoa] </td></tr>";			
					$a .="<tr  class='table-success'><td> Cpf/Cnpj Detran </td><td> $arrplacareboque[detrancpfcnpj]</td>";
					$a .="	<td> Nome Detran</td><td> $arrplacareboque[detrannomeproprietario] </td></tr>";
								
				}


				$a .="<tr><td> Endereco </td><td> $arrplacareboque[endereco]</td>";
					$a .="	<td> Cidade </td><td> $arrplacareboque[cidade] - $arrplacareboque[uf]</td>";				
					$a .="<tr><td> Fone </td><td> $arrplacareboque[fone]</td>";     
					$a .="	<td> Celular </td><td> $arrplacareboque[celular] N.o Seguranca $arrplacareboque[numeroseguranca]</td>	</tr>";   

					$a .="<tr><td>  Categoria  </td><td> $arrplacareboque[categoria] </td>";
					$a .="  <td> Ano Fabr </td><td>$arrplacareboque[anofabricacao]</td>		 ";
					$a .="<tr><td> Marca </td><td>$arrplacareboque[marca]</td>";
					$a .="	<td> Modelo </td><td>$arrplacareboque[modelo]</td></tr>		 ";
					$a .="<tr><td> Chassi </td><td>$arrplacareboque[chassi]</td>";
					$a .="	<td> Cor </td><td> $arrplacareboque[cor]</td></tr>";
					
//				$a .="</table><table width='100%' >";
					
					//******************************************
					// verifica se tem ocorrencia para o reboque
					//******************************************
					$sqlpesocorrencia = "
						select obs,
							to_char(datacriacao, 'DD/MM/YY') as datacriacao,
							usuario
						from tocorrencia
						where trim(chavedebusca) = '$arrplacareboque[placa]'	
						order by codiocorrencia ";
				
					$ressqlopescorrencia = pg_exec($sqlpesocorrencia);	
					
					if ( pg_numrows($ressqlopescorrencia) > 0 ){
					
						$avisos = "<br><img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle'> ATENCAO ! Este reboque possui avisos no sitema <br>";
						$avisos .= "<textarea readonly COLS=90 ROWS=1>";
						
						for ($i=0; $i < pg_numrows($ressqlopescorrencia ); $i++) {

							$arrpesocorrencia = pg_fetch_array($ressqlopescorrencia,$i,PGSQL_ASSOC);
						
							//$a .="<table class='tabla_cabecera' border='0'  align=center width='100%' >";
							
							$avisos .= "$arrpesocorrencia[datacriacao] $arrpesocorrencia[usuario] -  $arrpesocorrencia[obs] \n";
						}
						
						$avisos .= "</textarea>";
					}

					// verifico se o veiculo esta cadastrado fora do estado de residencia do
					// motorista

					if ($arrplacareboque['uf'] !=  $arrplacareboque['ufplaca']) {
					
						$avisos = "<br><img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle'> ATENCAO ! ALERTA Divergencia de UF <BR>";
						$avisos .= "<textarea readonly COLS=90 ROWS=1>";							
						$avisos .= "O proprietaro do veiculo possui residencia na UF $arrplacareboque[uf] e o veiculo foi registrado no Detran de $arrplacareboque[ufplaca] \n";
						$avisos .= "</textarea>";
					
					}
						
					
					//*****************************
					//* tela de consulta de reboque
					//*****************************
	
					//tira a ;e insere <br> para quebrar linha no html
					$auxobs = str_replace(";","<br>",$arrplacareboque['obs']);
					
					// cria uma ancora aqui
					$a.="<a name='ancoraplacareboque' id='ancoraplacareboque'></a>	</table>";
					
/*					
					$a .="<table align=center width='100%' >";
					$a .="<tr class='botonmoduleon'><td> $idademaximareboque $avisos </td><td><a href='../0ocorrencia/criaocorrencia.php?chavedebusca=$_GET[placareboque]&criterioporget=PLACA' > <div id='buttonaz'><img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle' target = '_blank'> Criar Alerta</div></a></td><tr>";
					$a .="<tr class='botonmoduleon'><td> $auxobs </td><td><a href='../-/interrisco/alterarespostapesquisa.php?&tipo=placa&placa=$arrplacareboque[placa]&obs=$arrplacareboque[obs]' TARGET = '_blank' ><div id='buttonaz'><img src='../0bmp/interno.gif' width='15' height='15'  border='0' align='absmiddle'> Edita</DIV></a></td><tr>";
					$a .="<tr class='botonmoduleon'><td colspan=2> <div id='div_obs_placareboque'></div> <div id='div_resp_gravaobs_placareboque'></div></td><tr>";
					$a .="</table>";
*/
		
				
					$a .="<table width='100%' cellpadding='3' > ";
					$a .="<tr><td><div role='alert'> $idademaximareboque  </div>	</td><td rowspan=5> <a href='#' onclick=historicocarregamentos('$arrpes[cpfcnpj]'); class='btn btn-success btn-sm mb-2'>  																		<img src='../0bmp/prancheta.jpg' width='20' height='20' align='absmiddle'  > 											Ver os Historicos</div></a><br>
																<a href='../0ocorrencia/criaocorrencia.php?chavedebusca=$_GET[placareboque]&criterioporget=PLACA' target = '_blank' class='btn btn-success btn-sm mb-2' > 				<img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle' target = '_blank' > 				Gera novo Alerta </a><br>
																<a href='../-/interrisco/alterarespostapesquisa.php?&tipo=placa&placa=$arrplacareboque[placa]&obs=$arrplacareboque[obs]' target = '_blank' class='btn btn-success btn-sm mb-2'>	<img src='../0bmp/interno.gif' width='15' height='15'  border='0' align='absmiddle'> 									Editar mensagens </a><br>
																<a href='#' onclick=pesquisa('$arrpes[cpfcnpj]','$_GET[grupo]','$_GET[protocolo]','$_GET[codipessoa]','$_GET[placacarro]','$_GET[placareboque]','$_GET[placasemireboque]','$_GET[placaterceiroreboque]','$_GET[conta]','$_GET[contaprincipal]','consulta','$_GET[enviaemailresposta]') class='btn btn-success btn-sm mb-2' > 	Atualizar Pagina (F5)</a>															
																</td></tr>";			
					$a .="<tr><td> $avisos 	</td></tr>";
					$a .="<tr><td> $auxobs 	</td></tr>";			
					$a .="<tr><td> <div id='div_obs_placareboque'></div></td></tr>";
					$a .="<tr><td> <div id='div_resp_gravaobs_placareboque'></div> </td></tr>";
					$a .="</table>";
	
		
					//botao do reboque
					
					$a.="<table  bgcolor=#DCDCDC><tr>	 ";
					
					
					if ($arrplacareboque['ck'] == '') 
						$a.="	<td align=center><a href='pesquisas.php' target='_blank' ><img src='../0bmp/$arrplacareboque[ck]a.png' align='absmiddle' border='0' width='20' height='20'>  <br> $arrplacareboque[ckdata] <br> $arrplacareboque[pesquisadodiasatraz] dias </a> ";
					else
						$a.="	<td align=center><a href='pesquisas.php' target='_blank' ><img src='../0bmp/$arrplacareboque[ck].png' align='absmiddle' border='0' width='20' height='20'>  <br> $arrplacareboque[ckdata] <br> $arrplacareboque[pesquisadodiasatraz] dias </a> ";
				
				
					if ($arrplacareboque['tipopessoa'] == 'CNPJ') {
						
						//RECEITA	
				
						if ($arrplacareboque['receitadata'] == date('d/m/Y') ) 
							$a.="	<td align=center><div id='divplacareboquereceita'><a href='#ancoraplacareboque' class='btn btn-light btn-sm' onclick=fun_obs_placareceita('$arrplacareboque[placa]','$arr[protocolo]','reboque')>       <img src='../0bmp/$arrplacareboque[ckreceita].png' align='absmiddle' border='0' width='20' height='20'>     R. Fazenda  <br>Agora <br>$arrplacareboque[receitausuario]</a></div> </td>";
						else
							$a.="	<td align=center><div id='divplacareboquereceita'><a href='#ancoraplacareboque' class='btn btn-light btn-sm' onclick=fun_obs_placareceita('$arrplacareboque[placa]','$arr[protocolo]','reboque')>       <img src='../0bmp/$arrplacareboque[ckreceita]a.png' align='absmiddle' border='0' width='20' height='20'>   R. Fazenda <br>$arrplacareboque[receitadata] <br>$arrplacareboque[receitausuario]</a></div></td>";
						
					}
					
				
					//se é para fazer cheque	
					//if ( (   $arr['fazercheque'] != 'f' )   ) 	 {				
			
			
						//vejo se a data é de hoje
					//	if ($arrplacareboque['chequedata'] == date('d/m/Y') ) 
					//		$a.="	<td align=center  bgcolor=#FFFFF0><div id='divplacareboquecheque'><a href='#ancoraplacareboque' onclick=fun_obs_placacheque('$arrplacareboque[placa]','$arr[protocolo]','reboque')>        <img src='../0bmp/$arrplacareboque[ckcheque].png' align='absmiddle' border='0' width='20' height='20'>        </div> Cheque <b> ($toleranciachque ) max. </b></a> <br>$arrplacareboque[chequedata] <br>$arrplacareboque[chequeusuario] <br><a class='btn btn-info btn-sm' href='#ancoraplacareboque' role='button' onclick=\"window.open('serverxmlccf.php?&cpfcnpj=$arrplacareboque[cpfcnpj]&codipessoa=$arrplacareboque[codipessoa]','','width=1000,height=700')\">Consultar Cheque</a></td> ";
					//	else								
					//		$a.="	<td align=center class=redonda><div id='divplacareboquecheque'>       <a href='#ancoraplacareboque' onclick=fun_obs_placacheque('$arrplacareboque[placa]','$arr[protocolo]','reboque')>        <img src='../0bmp/$arrplacareboque[ckcheque]a.png' align='absmiddle' border='0' width='20' height='20'>        </div> Cheque <b> ($toleranciachque ) max. </b></a> <br><a class='btn btn-info btn-sm' href='#ancoraplacareboque' role='button' onclick=\"window.open('serverxmlccf.php?&cpfcnpj=$arrplacareboque[cpfcnpj]&codipessoa=$arrplacareboque[codipessoa]','','width=1000,height=700')\" >Consultar Cheque</a> </td>";
							
					//}
					
					//licenciamento	
					if ($arrplacareboque['licenciamentodata'] == date('d/m/Y') ) 
						$a.="	<td align=center><div id='divplacareboquelicenciamento'><a href='#ancoraplacareboque' class='btn btn-light btn-sm mb-1'  onclick=fun_obs_placalicenciamento('$arrplacareboque[placa]','$arr[protocolo]','reboque')> <img src='../0bmp/$arrplacareboque[cklicenciamento].png' align='absmiddle' border='0' width='20' height='20'>  Ipva/Lic  <br>Agora <br>$arrplacareboque[licenciamentousuario]</a></div></td>";
					else
						$a.="	<td align=center><div id='divplacareboquelicenciamento'><a href='#ancoraplacareboque' class='btn btn-light btn-sm mb-1'  onclick=fun_obs_placalicenciamento('$arrplacareboque[placa]','$arr[protocolo]','reboque')> <img src='../0bmp/$arrplacareboque[cklicenciamento]a.png' align='absmiddle' border='0' width='20' height='20'>  Ipva/Lic <br>$arrplacareboque[licenciamentodata] <br>$arrplacareboque[licenciamentousuario]</a></div></div></td>";

					//certificado de propriedade		
					if ($arrplacareboque['propriedadedata'] == date('d/m/Y') ) 
						$a.="	<td align=center><div id='divplacareboquepropriedade'>  <a href='#ancoraplacareboque' class='btn btn-light btn-sm mb-1'  onclick=fun_obs_placapropriedade('$arrplacareboque[placa]','$arr[protocolo]','reboque')>   <img src='../0bmp/$arrplacareboque[ckpropriedade].png' align='absmiddle' border='0' width='20' height='20'>    Cert. Prop. <br>Agora<br>$arrplacareboque[propriedadeusuario]</a></div></td>";
					else
						$a.="	<td align=center ><div id='divplacareboquepropriedade'>  <a href='#ancoraplacareboque' class='btn btn-light btn-sm mb-1'  onclick=fun_obs_placapropriedade('$arrplacareboque[placa]','$arr[protocolo]','reboque')>   <img src='../0bmp/$arrplacareboque[ckpropriedade]a.png' align='absmiddle' border='0' width='20' height='20'>    Cert. Prop. <br>$arrplacareboque[propriedadedata] <br>$arrplacareboque[propriedadeusuario]</a></div></td> ";


				//grava dados da placa corect data
				
				$a.="<td align=center><div id='divplacareboquepropriedade'>  <a href='#ancoraplacareboque' class='btn btn-light btn-sm mb-1' onclick=\"window.open('correctdata.php?&placa=$arrplacareboque[placa]' ,'','width=1180,height=620,left=50,top=50');\">   Dados Placa <br> $arrplacareboque[correctdata] <br> $arrplacareboque[correctusuario] </a></div></td>";



					//botao da antt		
					//se nao precisar pesquisar antt nao negative
					if ($arr['parametroantt'] != 'NAO') {
						if ($arrplacareboque['anttdata'] == date('d/m/Y') ) 
							$a.="	<td align=center><div id='divplacareboqueantt'>         <a href='#ancoraplacareboque'  class='btn btn-light btn-sm mb-1' onclick=fun_obs_placaantt('$arrplacareboque[placa]','$arr[protocolo]','reboque')>          <img src='../0bmp/$arrplacareboque[ckantt].png' align='absmiddle' border='0' width='20' height='20'>          ANTT <br>Agora<br>$arrplacareboque[anttusuario]</a></div></td>";
						else
							$a.="	<td align=center ><div id='divplacareboqueantt'>         <a href='#ancoraplacareboque'  class='btn btn-light btn-sm mb-1' onclick=fun_obs_placaantt('$arrplacareboque[placa]','$arr[protocolo]','reboque')>          <img src='../0bmp/$arrplacareboque[ckantt]a.png' align='absmiddle' border='0' width='20' height='20'>          ANTT <br>$arrplacareboque[anttdata] <br>$arrplacareboque[anttusuario]</a></div> </td>";
						
						$a.="	<td align=center  ><div id='divplacareboqueantt'></div>    <a href='#ancoraplacareboque'  class='btn btn-light btn-sm mb-1' class='btn btn-success btn-sm' onclick=\"window.open('../-/antt/antttela.php' ,'','width=1180,height=620,left=50,top=50')\">   <img src='../0bmp/$arrplacareboque[ckantt].png' align='absmiddle' border='0' width='20' height='20'>          ANTT <br>$arrplacareboque[anttdata] <br>$arrplacareboque[anttusuario]</a></div></td>";
						
						
					}
					
					
					$a.="	<td align=center rowspan=2 width='15%'>  <a href='#ancoraplacareboque' onclick=pesquisa('$arrpes[cpfcnpj]','$_GET[grupo]','$_GET[protocolo]','$_GET[codipessoa]','$_GET[placacarro]','$_GET[placareboque]','$_GET[placasemireboque]','$_GET[placaterceiroreboque]','$_GET[conta]','$_GET[contaprincipal]','consulta','$_GET[enviaemailresposta]')><div id='buttonaz'><img src='../0bmp/atualiza.png' width='20' height='20'  border='0' align='absmiddle'>Atualiza (F5)</div></a></td></td></tr>";
					$a .="</table>	";
					
					
					$a .="</table>	";
						
			}
		}

		//****************************************
		//* placasemireboque
		//****************************************
		if (strlen($_GET['placasemireboque']) > 0 ) {
			
			$sqlplacasemireboque = "			
				select 
					tcarro.placa as placa,
					tcarro.codipessoa as codipessoa,
					tcarro.ufplaca,
					tcarro.renavan,
					tcarro.chassi,
					tcarro.anofabricacao,
					tcarro.cor,
					tcarro.categoria,
					tcarro.marca,
					tcarro.modelo,
					tcarro.antt,
					tcarro.detrancpfcnpj,
					tcarro.detrannomeproprietario,					
					tpessoa.nomepessoa,
					tpessoa.cep,
					tpessoa.cidade,
					tpessoa.uf,
					tpessoa.endereco,				
					tpessoa.fone,
					tpessoa.celular,
					tpessoa.tipopessoa,
					tpessoa.cpfcnpj,
					tcarro.numeroseguranca,					
					to_char((current_timestamp - ckdata), 'DD') as pesquisadodiasatraz,
					tvalidaplaca.ckdata,
					tvalidaplaca.cklicenciamento,					
					tvalidaplaca.placa as validaplaca_placa,
					tvalidaplaca.ckpropriedade,
					tvalidaplaca.obs,
					tvalidaplaca.ckcheque,
					tvalidaplaca.ckfone,
					tvalidaplaca.ckantt,
					tvalidaplaca.ckreceita,
					tvalidaplaca.ck,
					tvalidaplaca.receitadata,
					tvalidaplaca.receitausuario,
					tvalidaplaca.anttdata,
					tvalidaplaca.anttusuario,
					tvalidaplaca.chequedata,
					tvalidaplaca.chequeusuario,
					tvalidaplaca.fonedata,
					tvalidaplaca.foneusuario,
					tvalidaplaca.propriedadedata,
					tvalidaplaca.propriedadeusuario,
					tvalidaplaca.licenciamentodata,
					tvalidaplaca.licenciamentousuario,
					tvalidaplaca.experienciadata,
					tvalidaplaca.experienciausuario,
					tvalidaplaca.experienciack,
					tdoc.tipodoc,
					tantt.datacriacao,
					tdoc.quantidade,
					tdoc.extensao,
					tdoc.copiadocurl,
					tcorrectdata.data as correctdata,
					tcorrectdata.usuario as correctusuario, 
					tcorrectdata.obs as correctobs,
					to_char((current_timestamp - tdoc.dataentrada), 'DD') as tdocdataentradadias,
					to_char(tdoc.dataentrada, 'DD/MM/YY HH24:MI') as tdocdataentrada
					
				from
					tpessoa,
					tcarro 	LEFT OUTER JOIN tvalidaplaca ON (tcarro.placa = tvalidaplaca.placa)
							LEFT OUTER JOIN tdoc ON (tcarro.placa = tdoc.placa)
							LEFT OUTER JOIN tantt ON (tcarro.antt = tantt.antt)
							LEFT OUTER JOIN tcorrectdata ON (tcarro.placa = tcorrectdata.placa)
					

					
				where tcarro.placa = '$_GET[placasemireboque]'  and
					tcarro.codipessoa = tpessoa.codipessoa ";

			$resplacasemireboque = pg_exec($sqlplacasemireboque);
						
			if ( pg_numrows($resplacasemireboque) > 0 ){

				$arrplacasemireboque = pg_fetch_array($resplacasemireboque,0,PGSQL_ASSOC);
				
				// aqui eu verifico se tem algum registro em tvalidaplaca
				// se nao tiver eu faco um insert senao vai dar ploblema nos updates abaixo
				
				if ( trim($arrplacasemireboque['validaplaca_placa']) == '' ){
			  
					$sqlnew = " 
						insert into tvalidaplaca(placa,obs)
						values('$arrplacasemireboque[placa]','$_SESSION[usuario]')";
					
					$resnew = pg_exec($sqlnew);
								
				}	
			
			
				
			
				//if ( (   $arr['fazercheque'] != 	'f' ) ) 	 {
				//	if ($arrplacasemireboque['ckcheque'] != 't') 
				//		$possemireb = 'f';			
				//}//	
				
				if ($arrplacasemireboque['ckpropriedade'] != 't' )  
					$possemireb = 'f';			
				 
				 
				if ($arrplacasemireboque['tipopessoa'] == 'CNPJ') { 
					if ($arrplacasemireboque['ckreceita'] != 't' )  
						$possemireb = 'f';			
				}	
									
				if ($arrplacasemireboque['cklicenciamento'] != 't' )  
					$possemireb = 'f';			
					
				//se nao precisar pesquisar antt nao negative
				if ($arr[parametroantt] != 'NAO')
					if ($arrplacasemireboque['ckantt'] != 't' ) 
						$possemireb = 'f';			

				
				//*****************************************
				//* verifica o ano de fabricacao do semireb
				//*****************************************
				
				if ( ( (int)date('Y')  - (int)$arrplacasemireboque['anofabricacao'] )  >  (int)$arr['idademaximacarro']    ) {
					
			
					$idademaximasemireboque = "<table><tr class='redonda'><td align=center> <img src='../0bmp/a.png' align='absmiddle' border='0' width='25' height='25'> ATENCAO.:  semireboque com mais de $arr[idademaximacarro] anos de fabricacao!!!<td></tr></table>";
					$possemireb = 'f';	
					
					// sistema bloqueia automatico idade maxima Veiculo
					// logica: se ano fabricacao ainda nao existe ele executa a gravacao negativa em tvalida e tchamada
					// se ja fez essa validacao (atravez do strpos) dai nao grava para nao ficar duplicando respostas
					$posicao = strpos($arrplacasemireboque['obs'], ' Ano fabricacao ');

					if ($posicao == false) {
											
						$sqla = "
							update tvalidaplaca
							set obs  = ('(".date('dmy')." $_SESSION[usuario]) Ano fabricacao $arrplacasemireboque[placa]  maior que idade maxima $arr[idademaximacarro] Anos;' || obs)
							where placa = '$arrplacasemireboque[placa]'  ";
									
						$res = pg_exec($sqla);									
					
						$sqlchamadaw = "
							update tchamada
							set resposta = ('* Ano fabricacao $arrplacasemireboque[placa]  maior que idade maxima $arr[idademaximacarro] Anos;' || resposta)
							where protocolo = '$_GET[protocolo]'  ";
								
						$res = pg_exec($sqlchamadaw);
						
					
					}
					
					
					
				}
								
				//****************************
				//* pega os arquivos de upload
				//****************************
		
				if ( $arrplacasemireboque['copiadocurl'] != '' || $arrplacasemireboque['tipodoc'] != '') 				
					//funcao declarada aqui
					$a .= "<div class='alert alert-borda' style='border-color: blue;' role='alert'> Anexo(s): ".criabotaoanexo($resplacasemireboque,'placa')."</div>";
					
				
				$a .="<BR><table class='tabla_cabecera' border='0'  align=center width='100%' >";

				if ($arrplacasemireboque['detrancpfcnpj'] == '') {	
				
					$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=6><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'>SEMI REBOQUE: &nbsp;&nbsp; ($arrplacasemireboque[placa])  UF $arrplacasemireboque[ufplaca] &nbsp;&nbsp; Renavan: $arrplacasemireboque[renavan]- ANTT $arrplacasemireboque[antt] - Data Criacao $arrplacasemireboque[datacriacao] </td></tr>";
					$a .="<tr><td> Cpf/Cnpj </td><td> $arrplacasemireboque[cpfcnpj]</td>";
					$a .="	<td> Nome </td><td> $arrplacasemireboque[nomepessoa] </td></tr>";
					
				}else{
					//	detrancpfcnpj   detrannomeproprietario	 	
					$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=6><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'>SEMI REBOQUE: &nbsp;&nbsp; ($arrplacasemireboque[placa])  UF $arrplacasemireboque[ufplaca] &nbsp;&nbsp; Renavan: $arrplacasemireboque[renavan]- ANTT $arrplacasemireboque[antt] - Data Criacao $arrplacasemireboque[datacriacao] </td></tr>";
					
					$a .="<tr  class='table-info'><td> Cpf/Cnpj Antt </td><td> $arrplacasemireboque[cpfcnpj]</td>";
					$a .="	<td> Nome Antt</td><td> $arrplacasemireboque[nomepessoa] </td></tr>";			
					$a .="<tr  class='table-success'><td> Cpf/Cnpj Detran </td><td> $arrplacasemireboque[detrancpfcnpj]</td>";
					$a .="	<td> Nome Detran</td><td> $arrplacasemireboque[detrannomeproprietario] </td></tr>";
								
				}

				$a .="<tr><td> Endereco </td><td> $arrplacasemireboque[endereco]</td>";
				$a .="	<td> Cidade </td><td> $arrplacasemireboque[cidade] - $arrplacasemireboque[uf]</td>";				
				$a .="<tr><td> Fone </td><td> $arrplacasemireboque[fone]</td>";     
				$a .="	<td> Celular </td><td> $arrplacasemireboque[celular] N.o Seguranca: $arrplacasemireboque[numeroseguranca]</td>	</tr>";   


				$a .="<tr><td>  Categoria  </td><td> $arrplacasemireboque[categoria] </td>";
				$a .="   <td> Ano Fabr. </td><td>$arrplacasemireboque[anofabricacao]</td>		 ";
				$a .="<tr><td> Marca </td><td>$arrplacasemireboque[marca]</td>";
				$a .="	<td> Modelo </td><td>$arrplacasemireboque[modelo]</td></tr>		 ";
				$a .="<tr><td> Chassi </td><td>$arrplacasemireboque[chassi]</td>";
				$a .="	<td> Renavan </td><td>$arrplacasemireboque[renavan] - ANTT $arrplacasemireboque[antt]</td></tr>";
				$a .="	<td> Cor </td><td> $arrplacasemireboque[cor]</td></tr>";
					
//				$a .="</table><table align=center width='100%' >";
					
					//******************************************
					// verifica se tem ocorrencia para o semi reboque
					//******************************************
					$sqlpesocorrencia = "
						select obs,
							to_char(datacriacao, 'DD/MM/YY') as datacriacao,
							usuario
						from tocorrencia
						where trim(chavedebusca) = '$arrplacasemireboque[placa]'	
						order by codiocorrencia ";
				
					$ressqlopescorrencia = pg_exec($sqlpesocorrencia);	
					
					if ( pg_numrows($ressqlopescorrencia) > 0 ){
					
						$avisos = "<br><img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle'> ATENCAO ! Este Semi-reboque possui avisos no sitema <br>";
						$avisos = "<textarea readonly COLS=90 ROWS=1>";
						
						for ($i=0; $i < pg_numrows($ressqlopescorrencia ); $i++) {

							$arrpesocorrencia = pg_fetch_array($ressqlopescorrencia,$i,PGSQL_ASSOC);
						
							//$a .="<table class='tabla_cabecera' border='0'  align=center width='100%' >";
							
							$avisos .= "$arrpesocorrencia[datacriacao] $arrpesocorrencia[usuario] -  $arrpesocorrencia[obs] \n";
						}
						
						$avisos .= "</textarea>";
					}

					// verifico se o veiculo esta cadastrado fora do estado de residencia do
					// motorista

					if ($arrplacasemireboque['uf'] !=  $arrplacasemireboque['ufplaca']) {
					
						$avisos = "<br><img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle'> ATENCAO ! ALERTA Divergencia de UF <BR>";
						$avisos .= "<textarea readonly COLS=90 ROWS=1>";							
						$avisos .= "O proprietaro do veiculo possui residencia na UF $arrplacasemireboque[uf] e o veiculo foi registrado no Detran de $arrplacasemireboque[ufplaca] \n";
						$avisos .= "</textarea>";
					
					}
					
						
					
					
					//**********************************
					//* tela de consulta de semireboque
					//*****************************
					
					//tira a ;e insere <br> para quebrar linha no html
					$auxobs = str_replace(";","<br>",$arrplacasemireboque['obs']);
					
					// cria uma ancora aqui
					$a.="<a name='ancoraplacasemireboque' id='ancoraplacasemireboque'></a>	</table>";
						
		/*			$a .="<table align=center width='100%' >";
					$a .="<tr class='botonmoduleon'><td> $idademaximasemireboque $avisos </td><td><a href='../0ocorrencia/criaocorrencia.php?chavedebusca=$_GET[placasemireboque]&criterioporget=PLACA' > <div id='buttonaz'><img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle' target = '_blank' > Criar Alerta</div></a></td><tr>";
					$a .="<tr class='botonmoduleon'><td> $auxobs </td><td><a href='../-/interrisco/alterarespostapesquisa.php?&tipo=placa&placa=$arrplacasemireboque[placa]&obs=$arrplacasemireboque[obs]' TARGET = '_blank' ><div id='buttonaz'><img src='../0bmp/interno.gif' width='15' height='15'  border='0' align='absmiddle'> Edita</DIV></a></td><tr>";
					$a .="<tr class='botonmoduleon'><td colspan=2> <div id='div_obs_placasemireboque'></div> <div id='div_resp_gravaobs_placasemireboque'></div></td><tr>";
					$a .="</table>";		
		*/
		
					$a .="<table width='100%' cellpadding='3' > ";
					$a .="<tr><td><div role='alert'> $idademaximasemireboque  </div>	</td><td rowspan=5> <a href='#' onclick=historicocarregamentos('$arrpes[cpfcnpj]'); class='btn btn-success btn-sm mb-2'>  																		<img src='../0bmp/prancheta.jpg' width='20' height='20' align='absmiddle'  > 											Ver os Historicos</div></a><br>
																<a href='../0ocorrencia/criaocorrencia.php?chavedebusca=$_GET[placasemireboque]&criterioporget=PLACA' target = '_blank' class='btn btn-success btn-sm mb-2' > 				<img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle' target = '_blank' > 				Gera novo Alerta </a><br>
																<a href='../-/interrisco/alterarespostapesquisa.php?&tipo=placa&placa=$arrplacasemireboque[placa]&obs=$arrplacasemireboque[obs]' target = '_blank' class='btn btn-success btn-sm mb-2'>	<img src='../0bmp/interno.gif' width='15' height='15'  border='0' align='absmiddle'> 									Editar mensagens </a><br>
																<a href='#' onclick=pesquisa('$arrpes[cpfcnpj]','$_GET[grupo]','$_GET[protocolo]','$_GET[codipessoa]','$_GET[placacarro]','$_GET[placareboque]','$_GET[placasemireboque]','$_GET[placaterceiroreboque]','$_GET[conta]','$_GET[contaprincipal]','consulta','$_GET[enviaemailresposta]') class='btn btn-success btn-sm mb-2' > 	Atualizar Pagina (F5)</a>															
																</td></tr>";			
					$a .="<tr><td> $avisos 	</td></tr>";
					$a .="<tr><td> $auxobs 	</td></tr>";			
					$a .="<tr><td> <div id='div_obs_placasemireboque'></div></td></tr>";
					$a .="<tr><td> <div id='div_resp_gravaobs_placasemireboque'></div> </td></tr>";
					$a .="</table>";
	
		
		
		
		
		
		
		
					//**************
					//	$a.="<table width='100%'><tr class='botonmoduleon'>	 ";
					//	$a.="	<td align=center><a href='pesquisas.php' target='_blank' ><img src='../0bmp/$arrplacasemireboque[ck]a.png' align='absmiddle' border='0' width='20' height='20'>  <br> $arrplacasemireboque[ckdata] - $arrplacasemireboque[pesquisadodiasatraz] dias </a> ";
					//	$a.="	<td align=center><div id='divplacasemireboquelicenciamento'><a href='#ancoraplacasemireboque' onclick=fun_obs_placalicenciamento('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')> <img src='../0bmp/$arrplacasemireboque[cklicenciamento]a.png' align='absmiddle' border='0' width='20' height='20'> </div> Ipva/Lic </a></td>";
					//	$a.="	<td align=center><div id='divplacasemireboqueantt'>         <a href='#ancoraplacasemireboque' onclick=fun_obs_placaantt('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>          <img src='../0bmp/$arrplacasemireboque[ckantt]a.png' align='absmiddle' border='0' width='20' height='20'>          </div> ANTT </a></td>";
					//	$a.="	<td align=center><div id='divplacasemireboquecheque'>       <a href='#ancoraplacasemireboque' onclick=fun_obs_placacheque('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>        <img src='../0bmp/$arrplacasemireboque[ckcheque]a.png' align='absmiddle' border='0' width='20' height='20'>        </div> Cheque <b> ($toleranciachque ) max. </b></a> </td>";
					//	$a.="	<td align=center><div id='divplacasemireboquefone'>         <a href='#ancoraplacasemireboque' onclick=fun_obs_placafone('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>          <img src='../0bmp/$arrplacasemireboque[ckfone]a.png' align='absmiddle' border='0' width='20' height='20'>          </div> Fone  </a></td>";
					//	$a.="	<td align=center><div id='divplacasemireboquepropriedade'>  <a href='#ancoraplacasemireboque' onclick=fun_obs_placapropriedade('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>   <img src='../0bmp/$arrplacasemireboque[ckpropriedade]a.png' align='absmiddle' border='0' width='20' height='20'>   </div> Cert. Prop. </a></b> ";
					//	$a.="	<td align=center><div id='divplacasemireboquereceita'>      <a href='#ancoraplacasemireboque' onclick=fun_obs_placareceita('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>       <img src='../0bmp/$arrplacasemireboque[ckreceita]a.png' align='absmiddle' border='0' width='20' height='20'>       </div> R. Fazenda </a></td>";
					//	$a.="	<td align=center rowspan=2 width='15%'>  <a href='#ancoraplacasemireboque' onclick=pesquisa ('$arr[cpfcnpj]','$_GET[grupo]','$_GET[protocolo]','$_GET[codipessoa]','$_GET[placacarro]','$_GET[placareboque]','$_GET[placasemireboque]','$_GET[conta]','$_GET[contaprincipal]','consulta')><div id='buttonaz'><img src='../0bmp/atualiza.png' width='20' height='20'  border='0' align='absmiddle'>Atualiza (F5)</div></a></td></td></tr>";
					//	$a .="</table>	";					
					//
					//***************

					
					
					//botao do semireboque
					
					$a.="<table  bgcolor=#DCDCDC><tr>	 ";
					
					
					if ($arrplacasemireboque['ck'] == '') 
						$a.="	<td align=center ><a href='pesquisas.php' target='_blank' ><img src='../0bmp/$arrplacasemireboque[ck]a.png' align='absmiddle' border='0' width='20' height='20'>  <br> $arrplacasemireboque[ckdata] <br> $arrplacasemireboque[pesquisadodiasatraz] dias </a> ";
					else
						$a.="	<td align=center ><a href='pesquisas.php' target='_blank' ><img src='../0bmp/$arrplacasemireboque[ck].png' align='absmiddle' border='0' width='20' height='20'>  <br> $arrplacasemireboque[ckdata] <br>$arrplacasemireboque[pesquisadodiasatraz] dias </a> ";
				
					if ($arrplacasemireboque['tipopessoa'] == 'CNPJ') { 
						//RECEITA	
						if ($arrplacasemireboque['receitadata'] == date('d/m/Y') ) 
							$a.="	<td align=center ><div id='divplacasemireboquereceita'><a href='#ancoraplacasemireboque' class='btn btn-light btn-mg' onclick=fun_obs_placareceita('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>       <img src='../0bmp/$arrplacasemireboque[ckreceita].png' align='absmiddle' border='0' width='20' height='20'>       R. Fazenda  <br>Agora <br>$arrplacasemireboque[receitausuario]</a> </div></td>";
						else
							$a.="	<td align=center ><div id='divplacasemireboquereceita'><a href='#ancoraplacasemireboque' class='btn btn-light btn-mg' onclick=fun_obs_placareceita('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>       <img src='../0bmp/$arrplacasemireboque[ckreceita]a.png' align='absmiddle' border='0' width='20' height='20'>  R. Fazenda <br>$arrplacasemireboque[receitadata] <br>$arrplacasemireboque[receitausuario]</a> </div></td>";
					}
					
					//cheque	
					// se for servis nao precisa consultar cheque
					//if ( (   $arr['fazercheque'] != 	'f' )   ) 	 {				
								
					//	if ($arrplacasemireboque['chequedata'] == date('d/m/Y') ) 
					//		$a.="	<td align=center  bgcolor=#FFFFF0><div id='divplacasemireboquecheque'><a href='#ancoraplacasemireboque' onclick=fun_obs_placacheque('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>        <img src='../0bmp/$arrplacasemireboque[ckcheque].png' align='absmiddle' border='0' width='20' height='20'>        </div> Cheque <b> ($toleranciachque ) max. </b></a> <br>$arrplacasemireboque[chequedata] <br>$arrplacasemireboque[chequeusuario]  <br><a class='btn btn-info btn-sm' href='#ancoraplacasemireboque' role='button' onclick=\"window.open('serverxmlccf.php?&cpfcnpj=$arrplacasemireboque[cpfcnpj]&codipessoa=$arrplacasemireboque[codipessoa]','','width=800,height=500')\">Consutar Cheque</a></td>";
					//	else
							
					//			$a.="	<td align=center class=redonda><div id='divplacasemireboquecheque'>       <a href='#ancoraplacasemireboque' onclick=fun_obs_placacheque('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>        <img src='../0bmp/$arrplacasemireboque[ckcheque]a.png' align='absmiddle' border='0' width='20' height='20'>        </div> Cheque <b> ($toleranciachque ) max. </b></a>  <br><a class='btn btn-info btn-sm' href='#ancoraplacasemireboque' role='button' onclick=\"window.open('serverxmlccf.php?&cpfcnpj=$arrplacasemireboque[cpfcnpj]&codipessoa=$arrplacasemireboque[codipessoa]','','width=800,height=500')\">Consutar Cheque</a></td>";
							
					//}
					//licenciamento	
					if ($arrplacasemireboque['licenciamentodata'] == date('d/m/Y') ) 
						$a.="	<td align=center  ><div id='divplacasemireboquelicenciamento'><a href='#ancoraplacasemireboque'  class='btn btn-light btn-sm mb-1' onclick=fun_obs_placalicenciamento('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')> <img src='../0bmp/$arrplacasemireboque[cklicenciamento].png' align='absmiddle' border='0' width='20' height='20'>  Ipva/Lic  <br>Agora<br>$arrplacasemireboque[licenciamentousuario]</a> </div></td>";
					else
						$a.="	<td align=center ><div id='divplacasemireboquelicenciamento'><a href='#ancoraplacasemireboque' class='btn btn-light btn-sm mb-1'  onclick=fun_obs_placalicenciamento('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')> <img src='../0bmp/$arrplacasemireboque[cklicenciamento]a.png' align='absmiddle' border='0' width='20' height='20'> Ipva/Lic <br>$arrplacasemireboque[licenciamentodata] <br>$arrplacasemireboque[licenciamentousuario]</a></div></td>";

					//certificado de propriedade		
					if ($arrplacasemireboque['propriedadedata'] == date('d/m/Y') ) 
						$a.="	<td align=center  ><div id='divplacasemireboquepropriedade'>  <a href='#ancoraplacasemireboque'  class='btn btn-light btn-sm mb-1' onclick=fun_obs_placapropriedade('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>   <img src='../0bmp/$arrplacasemireboque[ckpropriedade].png' align='absmiddle' border='0' width='20' height='20'>   Cert. Prop. <br>Agora<br>$arrplacasemireboque[propriedadeusuario]</a> </div></td>";
					else
						$a.="	<td align=center ><div id='divplacasemireboquepropriedade'>  <a href='#ancoraplacasemireboque'  class='btn btn-light btn-sm mb-1' onclick=fun_obs_placapropriedade('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>   <img src='../0bmp/$arrplacasemireboque[ckpropriedade]a.png' align='absmiddle' border='0' width='20' height='20'>    Cert. Prop. <br>$arrplacasemireboque[propriedadedata] <br>$arrplacasemireboque[propriedadeusuario]</a> </div></b> ";
					
									//grava placa
				
				$a.="<td align=center><div id='divplacasemireboquepropriedade'>  <a href='#ancoraplacasemireboque' class='btn btn-light btn-sm mb-1' onclick=\"window.open('correctdata.php?&placa=$arrplacasemireboque[placa]' ,'','width=1180,height=620,left=50,top=50');\">   Dados Placa <br> $arrplacasemireboque[correctdata] <br> $arrplacasemireboque[correctusuario] </a></div></td>";


					
					
					//botao da antt		
					//se nao precisar pesquisar antt nao negative
					if ($arr['parametroantt'] != 'NAO') {
						if ($arrplacasemireboque['anttdata'] == date('d/m/Y') ) 
							$a.="	<td align=center ><div id='divplacasemireboqueantt'>         <a href='#ancoraplacasemireboque'  class='btn btn-light btn-sm mb-1' onclick=fun_obs_placaantt('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>          <img src='../0bmp/$arrplacasemireboque[ckantt].png' align='absmiddle' border='0' width='20' height='20'>         ANTT <br>Agora<br>$arrplacasemireboque[anttusuario]</a> </div></td>";
						else
							$a.="	<td align=center ><div id='divplacasemireboqueantt'>         <a href='#ancoraplacasemireboque'  class='btn btn-light btn-sm mb-1' onclick=fun_obs_placaantt('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>          <img src='../0bmp/$arrplacasemireboque[ckantt]a.png' align='absmiddle' border='0' width='20' height='20'>        ANTT <br>$arrplacasemireboque[anttdata] <br>$arrplacasemireboque[anttusuario]</a> </div></td>";
					
						$a.="	<td align=center ><div id='divplacasemireboqueantt'></div>    <a href='#ancoraplacasemireboque'  class='btn btn-light btn-sm mb-1' class='btn btn-success btn-sm' onclick=\"window.open('../-/antt/antttela.php' ,'','width=1180,height=620,left=50,top=50')\">   <img src='../0bmp/$arrplacasemireboque[ckantt].png' align='absmiddle' border='0' width='20' height='20'>   ANTT <br>$arrplacasemireboque[anttdata] <br>$arrplacasemireboque[anttusuario]</a> </div></td>";
					
					
					}
					//fone
					//if ($arrplacasemireboque['fonedata'] == date('d/m/Y') ) 
					//	$a.="	<td align=center  bgcolor=#FFFFF0><div id='divplacasemireboquefone'>         <a href='#ancoraplacasemireboque' onclick=fun_obs_placafone('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>          <img src='../0bmp/$arrplacasemireboque[ckfone].png' align='absmiddle' border='0' width='20' height='20'>          </div> Fone  </a><br>$arrplacasemireboque[fonedata] <br>$arrplacasemireboque[foneusuario]</td>";
					//else	
					//	$a.="	<td align=center class=redonda><div id='divplacasemireboquefone'>         <a href='#ancoraplacasemireboque' onclick=fun_obs_placafone('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>          <img src='../0bmp/$arrplacasemireboque[ckfone]a.png' align='absmiddle' border='0' width='20' height='20'>          </div> Fone  </a></td>";
					
					//experiencia bloqueia ultimo carregamento
					//if ($arr['bloqueiaspot'] == 'SIM') {
					//	if ($arrplacasemireboque['experienciadata'] == date('d/m/Y') ) 
					//		$a.="	<td align=center  bgcolor=#FFFFF0><div id='divplacasemireboqueexperiencia'>         <a href='#ancoraplacasemireboque' onclick=fun_obs_placaexperiencia('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>          <img src='../0bmp/$arrplacasemireboque[experienciack].png' align='absmiddle' border='0' width='20' height='20'>          </div> Experiencia  </a><br>$arrplacasemireboque[experienciadata] <br>$arrplacasemireboque[experienciausuario]</td>";
					//	else	
					//		$a.="	<td align=center class=redonda><div id='divplacasemireboqueexperiencia'>         <a href='#ancoraplacasemireboque' onclick=fun_obs_placaexperiencia('$arrplacasemireboque[placa]','$arr[protocolo]','semireboque')>          <img src='../0bmp/$arrplacasemireboque[experienciack]a.png' align='absmiddle' border='0' width='20' height='20'>          </div> Experiencia  </a></td>";
					//}
									//A FUNCAO PESQUISA ( TA EM  registrospendentes.php
					$a.="	<td align=center rowspan=2 width='15%'>  <a href='#ancoraplacasemireboque' onclick=pesquisa('$arr[cpfcnpj]','$_GET[grupo]','$_GET[protocolo]','$_GET[codipessoa]','$_GET[placacarro]','$_GET[placareboque]','$_GET[placasemireboque]','$_GET[placaterceiroreboque]','$_GET[conta]','$_GET[contaprincipal]','consulta','$_GET[enviaemailresposta]')><div id='buttonaz'><img src='../0bmp/atualiza.png' width='20' height='20'  border='0' align='absmiddle'>Atualiza (F5)</div></a></td></td></tr>";
					$a .="</table>	";
								
			}
		}	

		//****************************************
		//* placaterceiroreboque
		//****************************************
		if (strlen($_GET['placaterceiroreboque']) > 0 ) {
			
			$sqlplacaterceiroreboque = "			
				select 
					tcarro.placa as placa,
					tcarro.codipessoa as codipessoa,
					tcarro.ufplaca,
					tcarro.renavan,
					tcarro.chassi,
					tcarro.anofabricacao,
					tcarro.cor,
					tcarro.categoria,
					tcarro.marca,
					tcarro.modelo,
					tcarro.antt,
					tcarro.detrancpfcnpj,
					tcarro.detrannomeproprietario,
					tpessoa.nomepessoa,
					tpessoa.cep,
					tpessoa.cidade,
					tpessoa.uf,
					tpessoa.endereco,				
					tpessoa.fone,
					tpessoa.celular,
					tpessoa.tipopessoa,
					tpessoa.cpfcnpj,
					tcarro.numeroseguranca,					
					to_char((current_timestamp - ckdata), 'DD') as pesquisadodiasatraz,
					tvalidaplaca.ckdata,
					tvalidaplaca.cklicenciamento,					
					tvalidaplaca.placa as validaplaca_placa,
					tvalidaplaca.ckpropriedade,
					tvalidaplaca.obs,
					tvalidaplaca.ckcheque,
					tvalidaplaca.ckfone,
					tvalidaplaca.ckantt,
					tvalidaplaca.ckreceita,
					tvalidaplaca.ck,
					tvalidaplaca.receitadata,
					tvalidaplaca.receitausuario,
					tvalidaplaca.anttdata,
					tvalidaplaca.anttusuario,
					tvalidaplaca.chequedata,
					tvalidaplaca.chequeusuario,
					tvalidaplaca.fonedata,
					tvalidaplaca.foneusuario,
					tvalidaplaca.propriedadedata,
					tvalidaplaca.propriedadeusuario,
					tvalidaplaca.licenciamentodata,
					tvalidaplaca.licenciamentousuario,
					tvalidaplaca.experienciadata,
					tvalidaplaca.experienciausuario,
					tvalidaplaca.experienciack,
					tantt.datacriacao,
					tdoc.tipodoc,
					tdoc.quantidade,
					tdoc.extensao,
					tdoc.copiadocurl,
					tcorrectdata.data as correctdata,
					tcorrectdata.usuario as correctusuario, 
					tcorrectdata.obs as correctobs,
					to_char((current_timestamp - tdoc.dataentrada), 'DD') as tdocdataentradadias,
					to_char(tdoc.dataentrada, 'DD/MM/YY HH24:MI') as tdocdataentrada
					
				from
					tpessoa,
					tcarro 	LEFT OUTER JOIN tvalidaplaca ON (tcarro.placa = tvalidaplaca.placa)
							LEFT OUTER JOIN tdoc ON (tcarro.placa = tdoc.placa)
							LEFT OUTER JOIN tantt ON (tcarro.antt = tantt.antt)
							LEFT OUTER JOIN tcorrectdata ON (tcarro.placa = tcorrectdata.placa)
					

				where tcarro.placa = '$_GET[placaterceiroreboque]'  and
					tcarro.codipessoa = tpessoa.codipessoa ";
					

			$resplacaterceiroreboque = pg_exec($sqlplacaterceiroreboque);
						
			if ( pg_numrows($resplacaterceiroreboque) > 0 ){

				$arrplacaterceiroreboque = pg_fetch_array($resplacaterceiroreboque,0,PGSQL_ASSOC);
				
				// aqui eu verifico se tem algum registro em tvalidaplaca
				// se nao tiver eu faco um insert senao vai dar ploblema nos updates abaixo
				
				if ( trim($arrplacaterceiroreboque['validaplaca_placa']) == '' ){
			  
					$sqlnew = " 
						insert into tvalidaplaca(placa,obs)
						values('$arrplacaterceiroreboque[placa]','$_SESSION[usuario]')";
					
					$resnew = pg_exec($sqlnew);
								
				}	
									
					//if ( (   $arr['fazercheque'] != 	'f' )  ) 	 {
					//	if ($arrplacaterceiroreboque['ckcheque'] != 't')  
					//		$posterceiroreb = 'f';			
					//}	
					 
					if ($arrplacaterceiroreboque['ckpropriedade'] != 't' ) 
						$posterceiroreb = 'f';			
					 
					if ($arrplacaterceiroreboque['tipopessoa'] == 'CNPJ') { 
						if ($arrplacaterceiroreboque['ckreceita'] != 't' )  
							$posterceiroreb = 'f';			
					}	
					 					
					if ($arrplacaterceiroreboque['cklicenciamento'] != 't' )  
						$posterceiroreb = 'f';			
					 
					//se nao precisar pesquisar antt nao negative
					if ($arr['parametroantt'] != 'NAO')
						if ($arrplacaterceiroreboque['ckantt'] != 't' ) 
							$posterceiroreb = 'f';			
							
					//if ($arrplacaterceiroreboque['ckfone'] != 't' ) {
					//	$posterceiroreb = 'f';			
					//}
													
				//*****************************************
				//* verifica o ano de fabricacao do terceiroreb
				//*****************************************
				//10  + 2014 <= 2023
				
				    
				if ( ( (int)date('Y')  - (int)$arrplacaterceiroreboque['anofabricacao'] )  >  (int)$arr['idademaximacarro']    ) {
				
				
					$idademaximaterceiroreboque = "<table><tr class='redonda'><td align=center> <img src='../0bmp/a.png' align='absmiddle' border='0' width='25' height='25'> ATENCAO.:  terceiroreboque com mais de $arr[idademaximacarro] anos de fabricacao !!!<td></tr></table>";
					$posterceiroreb = 'f';	
					
					// sistema bloqueia automatico idade maxima Veiculo
					// logica: se ano fabricacao ainda nao existe ele executa a gravacao negativa em tvalida e tchamada
					// se ja fez essa validacao (atravez do strpos) dai nao grava para nao ficar duplicando respostas
					$posicao = strpos($arrplacaterceiroreboque['obs'], ' Ano fabricacao ');

					if ($posicao == false) {
											
						$sqla = "
							update tvalidaplaca
							set obs  = ('(".date('dmy')." $_SESSION[usuario]) Ano fabricacao $arrplacaterceiroreboque[placa]  maior que idade maxima $arr[idademaximacarro] Anos;' || obs)
							where placa = '$arrplacaterceiroreboque[placa]'  ";
									
						$res = pg_exec($sqla);									
					
						$sqlchamadaw = "
							update tchamada
							set resposta = ('* Ano fabricacao $arrplacaterceiroreboque[placa]  maior que idade maxima $arr[idademaximacarro] Anos;' || resposta)
							where protocolo = '$_GET[protocolo]'  ";
								
						$res = pg_exec($sqlchamadaw);						
					
					}
					
					
				}
								
				//****************************
				//* pega os arquivos de upload
				//****************************
								
				if ( $arrplacaterceiroreboque['copiadocurl'] != '' || $arrplacaterceiroreboque['tipodoc'] != '') 				
					//funcao declarada aqui
					$a .= "<div class='alert alert-borda' style='border-color: blue;' role='alert'> Anexo(s): ".criabotaoanexo($resplacaterceiroreboque,'placa')."</div>";

								
					$a .="<BR><table class='tabla_cabecera' border='0'  align=center width='100%' >";
					
					if ($arrplacaterceiroreboque['detrancpfcnpj'] == '') {	
					
						$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=6><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'>TERCEIRO REBOQUE: &nbsp;&nbsp; ($arrplacaterceiroreboque[placa])  UF $arrplacaterceiroreboque[ufplaca] &nbsp;&nbsp; Renavan: $arrplacaterceiroreboque[renavan]- ANTT $arrplacaterceiroreboque[antt] - Data Criacao $arrplacaterceiroreboque[datacriacao] </td></tr>";
						$a .="<tr><td> Cpf/Cnpj </td><td> $arrplacaterceiroreboque[cpfcnpj]</td>";
						$a .="	<td> Nome </td><td> $arrplacaterceiroreboque[nomepessoa] </td></tr>";
						
					}else{
						//	detrancpfcnpj   detrannomeproprietario	 	
						$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=6><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'>TERCEIRO REBOQUE: &nbsp;&nbsp; ($arrplacaterceiroreboque[placa])  UF $arrplacaterceiroreboque[ufplaca] &nbsp;&nbsp; Renavan: $arrplacaterceiroreboque[renavan]- ANTT $arrplacaterceiroreboque[antt] - Data Criacao $arrplacaterceiroreboque[datacriacao] </td></tr>";
						
						$a .="<tr class='table-info'><td> Cpf/Cnpj Antt </td><td> $arrplacaterceiroreboque[cpfcnpj]</td>";
						$a .="	<td> Nome Antt</td><td> $arrplacaterceiroreboque[nomepessoa] </td></tr>";			
						$a .="<tr class='table-success'><td> Cpf/Cnpj Detran </td><td> $arrplacaterceiroreboque[detrancpfcnpj]</td>";
						$a .="	<td> Nome Detran</td><td> $arrplacaterceiroreboque[detrannomeproprietario] </td></tr>";
									
					}
	
					$a .="<tr><td> Endereco </td><td> $arrplacaterceiroreboque[endereco]</td>";
					$a .="	<td> Cidade </td><td> $arrplacaterceiroreboque[cidade] - $arrplacaterceiroreboque[uf]</td>";				
					$a .="<tr><td> Fone </td><td> $arrplacaterceiroreboque[fone]</td>";     
					$a .="	<td> Celular </td><td> $arrplacaterceiroreboque[celular] N.o Seguranca: $arrplacaterceiroreboque[numeroseguranca]</td>	</tr>";   

					$a .="<tr><td>  Categoria  </td><td> $arrplacaterceiroreboque[categoria] </td>";
					$a .="   <td> Ano Fabr. </td><td>$arrplacaterceiroreboque[anofabricacao]</td>		 ";
					$a .="<tr><td> Marca </td><td>$arrplacaterceiroreboque[marca]</td>";
					$a .="	<td> Modelo </td><td>$arrplacaterceiroreboque[modelo]</td></tr>		 ";
					$a .="<tr><td> Chassi </td><td>$arrplacaterceiroreboque[chassi]</td>";
					$a .="	<td> Renavan </td><td>$arrplacaterceiroreboque[renavan] - ANTT $arrplacaterceiroreboque[antt]</td></tr>";
					$a .="	<td> Cor </td><td> $arrplacaterceiroreboque[cor]</td></tr>";
					
//				$a .="</table><table align=center width='100%' >";
					
					//******************************************
					// verifica se tem ocorrencia para o terceiro reboque
					//******************************************
					$sqlpesocorrencia = "
						select obs,
							to_char(datacriacao, 'DD/MM/YY') as datacriacao,
							usuario
						from tocorrencia
						where trim(chavedebusca) = '$arrplacaterceiroreboque[placa]'	
						order by codiocorrencia ";
				
					$ressqlopescorrencia = pg_exec($sqlpesocorrencia);	
					
					if ( pg_numrows($ressqlopescorrencia) > 0 ){
					
						$avisos = "<br><img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle'> ATENCAO ! Este terceiro-reboque possui avisos no sitema <br>";
						$avisos = "<textarea readonly COLS=90 ROWS=1>";
						
						for ($i=0; $i < pg_numrows($ressqlopescorrencia ); $i++) {

							$arrpesocorrencia = pg_fetch_array($ressqlopescorrencia,$i,PGSQL_ASSOC);
						
							//$a .="<table class='tabla_cabecera' border='0'  align=center width='100%' >";
							
							$avisos .= "$arrpesocorrencia[datacriacao] $arrpesocorrencia[usuario] -  $arrpesocorrencia[obs] \n";
						}
						
						$avisos .= "</textarea>";
					}

					// verifico se o veiculo esta cadastrado fora do estado de residencia do
					// motorista

					if ($arrplacaterceiroreboque['uf'] !=  $arrplacaterceiroreboque['ufplaca']) {
					
						$avisos = "<br><img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle'> ATENCAO ! ALERTA Divergencia de UF <BR>";
						$avisos .= "<textarea readonly COLS=90 ROWS=1>";							
						$avisos .= "O proprietaro do veiculo possui residencia na UF $arrplacaterceiroreboque[uf] e o veiculo foi registrado no Detran de $arrplacaterceiroreboque[ufplaca] \n";
						$avisos .= "</textarea>";
					
					}
										
					//**********************************
					//* tela de consulta de terceiroreboque
					//*****************************
					
					//tira a ;e insere <br> para quebrar linha no html
					$auxobs = str_replace(";","<br>",$arrplacaterceiroreboque['obs']);
					
					// cria uma ancora aqui
					$a.="<a name='ancoraplacaterceiroreboque' id='ancoraplacaterceiroreboque'></a>	</table>";
						
					/*	
					$a .="<table align=center width='100%' >";
					$a .="<tr class='botonmoduleon'><td> $idademaximaterceiroreboque $avisos </td><td><a href='../0ocorrencia/criaocorrencia.php?chavedebusca=$_GET[placaterceiroreboque]&criterioporget=PLACA' > <div id='buttonaz'><img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle' target = '_blank' > Criar Alerta</div></a></td><tr>";
					$a .="<tr class='botonmoduleon'><td> $auxobs </td><td><a href='../-/interrisco/alterarespostapesquisa.php?&tipo=placa&placa=$arrplacaterceiroreboque[placa]&obs=$arrplacaterceiroreboque[obs]' TARGET = '_blank' ><div id='buttonaz'><img src='../0bmp/interno.gif' width='15' height='15'  border='0' align='absmiddle'> Edita</DIV></a></td><tr>";
					$a .="<tr class='botonmoduleon'><td colspan=2> <div id='div_obs_placaterceiroreboque'></div> <div id='div_resp_gravaobs_placaterceiroreboque'></div></td><tr>";
					$a .="</table>";		
					*/		
							

					$a .="<table width='100%' cellpadding='3' > ";
					$a .="<tr><td><div role='alert'> $idademaximaterceiroreboque  </div>	</td><td rowspan=5> <a href='#' onclick=historicocarregamentos('$arrpes[cpfcnpj]'); class='btn btn-success btn-sm mb-2'>  																		<img src='../0bmp/prancheta.jpg' width='20' height='20' align='absmiddle'  > 											Ver os Historicos</div></a><br>
																<a href='../0ocorrencia/criaocorrencia.php?chavedebusca=$_GET[placaterceiroreboque]&criterioporget=PLACA' target = '_blank' class='btn btn-success btn-sm mb-2' > 				<img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle' target = '_blank' > 				Gera novo Alerta </a><br>
																<a href='../-/interrisco/alterarespostapesquisa.php?&tipo=placa&placa=$arrplacaterceiroreboque[placa]&obs=$arrplacaterceiroreboque[obs]' target = '_blank' class='btn btn-success btn-sm mb-2'>	<img src='../0bmp/interno.gif' width='15' height='15'  border='0' align='absmiddle'> 									Editar mensagens </a><br>
																<a href='#' onclick=pesquisa('$arrpes[cpfcnpj]','$_GET[grupo]','$_GET[protocolo]','$_GET[codipessoa]','$_GET[placacarro]','$_GET[placareboque]','$_GET[placasemireboque]','$_GET[placaterceiroreboque]','$_GET[conta]','$_GET[contaprincipal]','consulta','$_GET[enviaemailresposta]') class='btn btn-success btn-sm mb-2' > 	Atualizar Pagina (F5)</a>															
																</td></tr>";			
					$a .="<tr><td> $avisos 	</td></tr>";
					$a .="<tr><td> $auxobs 	</td></tr>";			
					$a .="<tr><td> <div id='div_obs_placaterceiroreboque'></div></td></tr>";
					$a .="<tr><td> <div id='div_resp_gravaobs_placaterceiroreboque'></div> </td></tr>";
					$a .="</table>";								
							
					//botao do terceiro reboque		
							
					$a.="<table width='100%'  bgcolor=#DCDCDC><tr>	 ";
										
					if ($arrplacaterceiroreboque['ck'] == '') 
						$a.="	<td align=center ><a href='pesquisas.php' target='_blank' ><img src='../0bmp/$arrplacaterceiroreboque[ck]a.png' align='absmiddle' border='0' width='20' height='20'>  <br> $arrplacaterceiroreboque[ckdata] <br> $arrplacaterceiroreboque[pesquisadodiasatraz] dias </a> ";
					else
						$a.="	<td align=center ><a href='pesquisas.php' target='_blank' ><img src='../0bmp/$arrplacaterceiroreboque[ck].png' align='absmiddle' border='0' width='20' height='20'>  <br> $arrplacaterceiroreboque[ckdata] <br> $arrplacaterceiroreboque[pesquisadodiasatraz] dias </a> ";
				
					if ($arrplacaterceiroreboque['tipopessoa'] == 'CNPJ') { 
			
						//RECEITA	
						if ($arrplacaterceiroreboque['receitadata'] == date('d/m/Y') ) 
							$a.="	<td align=center ><div id='divplacaterceiroreboquereceita'><a href='#ancoraplacaterceiroreboque'  class='btn btn-light btn-sm mb-1' onclick=fun_obs_placareceita('$arrplacaterceiroreboque[placa]','$arr[protocolo]','terceiroreboque')>       <img src='../0bmp/$arrplacaterceiroreboque[ckreceita].png' align='absmiddle' border='0' width='20' height='20'>      R. Fazenda  <br>Agora<br>$arrplacaterceiroreboque[receitausuario]</a></div></td>";
						else
							$a.="	<td align=center ><div id='divplacaterceiroreboquereceita'><a href='#ancoraplacaterceiroreboque'  class='btn btn-light btn-sm mb-1' onclick=fun_obs_placareceita('$arrplacaterceiroreboque[placa]','$arr[protocolo]','terceiroreboque')>       <img src='../0bmp/$arrplacaterceiroreboque[ckreceita]a.png' align='absmiddle' border='0' width='20' height='20'>      R. Fazenda <br>$arrplacaterceiroreboque[receitadata] <br>$arrplacaterceiroreboque[receitausuario]</a></div></td>";
					}	
					
					//cheque	
					// se for servis nao precisa consultar cheque
					
					//if ( (   $arr['fazercheque'] != 	'f' )   ) 	 {				
		
					//	if ($arrplacaterceiroreboque['chequedata'] == date('d/m/Y') ) 
					//		$a.="	<td align=center  bgcolor=#FFFFF0><div id='divplacaterceiroreboquecheque'><a href='#ancoraplacaterceiroreboque' onclick=fun_obs_placacheque('$arrplacaterceiroreboque[placa]','$arr[protocolo]','terceiroreboque')>        <img src='../0bmp/$arrplacaterceiroreboque[ckcheque].png' align='absmiddle' border='0' width='20' height='20'>        </div> Cheque <b> ($toleranciachque ) max. </b></a> <br>$arrplacaterceiroreboque[chequedata] <br>$arrplacaterceiroreboque[chequeusuario]  <br><a class='btn btn-info btn-sm' href='#ancoraplacaterceiroreboque' role='button' onclick=\"window.open('serverxmlccf.php?&cpfcnpj=$arrplacaterceiroreboque[cpfcnpj]&codipessoa=$arrplacaterceiroreboque[codipessoa]','','width=800,height=500')\">Consutar Cheque</a></td>";
					//	else
							
					//			$a.="	<td align=center class=redonda><div id='divplacaterceiroreboquecheque'>       <a href='#ancoraplacaterceiroreboque' onclick=fun_obs_placacheque('$arrplacaterceiroreboque[placa]','$arr[protocolo]','terceiroreboque')>        <img src='../0bmp/$arrplacaterceiroreboque[ckcheque]a.png' align='absmiddle' border='0' width='20' height='20'>        </div> Cheque <b> ($toleranciachque ) max. </b></a>  <br><a class='btn btn-info btn-sm' href='#ancoraplacaterceiroreboque' role='button' onclick=\"window.open('serverxmlccf.php?&cpfcnpj=$arrplacaterceiroreboque[cpfcnpj]&codipessoa=$arrplacaterceiroreboque[codipessoa]','','width=800,height=500')\">Consutar Cheque</a></td>";
									
					//}
					
					//licenciamento	
					if ($arrplacaterceiroreboque['licenciamentodata'] == date('d/m/Y') ) 
						$a.="	<td align=center ><div id='divplacaterceiroreboquelicenciamento'><a href='#ancoraplacaterceiroreboque'  class='btn btn-light btn-sm mb-1' onclick=fun_obs_placalicenciamento('$arrplacaterceiroreboque[placa]','$arr[protocolo]','terceiroreboque')> <img src='../0bmp/$arrplacaterceiroreboque[cklicenciamento].png' align='absmiddle' border='0' width='20' height='20'>  Ipva/Lic  <br>Agora <br>$arrplacaterceiroreboque[licenciamentousuario]</a></div></td>";
					else
						$a.="	<td align=center><div id='divplacaterceiroreboquelicenciamento'><a href='#ancoraplacaterceiroreboque'  class='btn btn-light btn-sm mb-1' onclick=fun_obs_placalicenciamento('$arrplacaterceiroreboque[placa]','$arr[protocolo]','terceiroreboque')> <img src='../0bmp/$arrplacaterceiroreboque[cklicenciamento]a.png' align='absmiddle' border='0' width='20' height='20'>  Ipva/Lic <br>$arrplacaterceiroreboque[licenciamentodata] <br>$arrplacaterceiroreboque[licenciamentousuario]</a></div></td>";

					//certificado de propriedade		
					if ($arrplacaterceiroreboque['propriedadedata'] == date('d/m/Y') ) 
						$a.="	<td align=center ><div id='divplacaterceiroreboquepropriedade'>  <a href='#ancoraplacaterceiroreboque'  class='btn btn-light btn-sm mb-1' onclick=fun_obs_placapropriedade('$arrplacaterceiroreboque[placa]','$arr[protocolo]','terceiroreboque')>   <img src='../0bmp/$arrplacaterceiroreboque[ckpropriedade].png' align='absmiddle' border='0' width='20' height='20'>   Cert. Prop.<br>Agora <br>$arrplacaterceiroreboque[propriedadeusuario]</a></div></td>";
					else
						$a.="	<td align=center ><div id='divplacaterceiroreboquepropriedade'>  <a href='#ancoraplacaterceiroreboque'  class='btn btn-light btn-sm mb-1' onclick=fun_obs_placapropriedade('$arrplacaterceiroreboque[placa]','$arr[protocolo]','terceiroreboque')>   <img src='../0bmp/$arrplacaterceiroreboque[ckpropriedade]a.png' align='absmiddle' border='0' width='20' height='20'>    Cert. Prop. <br>$arrplacaterceiroreboque[propriedadedata] <br>$arrplacaterceiroreboque[propriedadeusuario]</a></div></td> ";
					
									//grava placa

						
				$a.="<td align=center><div id='divplacaterceiroreboquepropriedade'>  <a href='#ancoraplacaterceiroreboque' class='btn btn-light btn-sm mb-1' onclick=\"window.open('correctdata.php?&placa=$arrplacaterceiroreboque[placa]' ,'','width=1180,height=620,left=50,top=50');\">   Dados Placa <br> $arrplacaterceiroreboque[correctdata] <br> $arrplacaterceiroreboque[correctusuario] </a></div></td>";

	
					//botao da antt
					//se nao precisar pesquisar antt nao negative
					if ($arr['parametroantt'] != 'NAO')	{				
						if ($arrplacaterceiroreboque['anttdata'] == date('d/m/Y') ) 
							$a.="	<td align=center><div id='divplacaterceiroreboqueantt'>         <a href='#ancoraplacaterceiroreboque' class='btn btn-light btn-sm mb-1'' onclick=fun_obs_placaantt('$arrplacaterceiroreboque[placa]','$arr[protocolo]','terceiroreboque')>          <img src='../0bmp/$arrplacaterceiroreboque[ckantt].png' align='absmiddle' border='0' width='20' height='20'>           ANTT <br>Agora <br>$arrplacaterceiroreboque[anttusuario] </a></div> </td>";
						else
							$a.="	<td align=center ><div id='divplacaterceiroreboqueantt'>         <a href='#ancoraplacaterceiroreboque' class='btn btn-light btn-sm mb-1' onclick=fun_obs_placaantt('$arrplacaterceiroreboque[placa]','$arr[protocolo]','terceiroreboque')>          <img src='../0bmp/$arrplacaterceiroreboque[ckantt]a.png' align='absmiddle' border='0' width='20' height='20'>          ANTT <br>$arrplacaterceiroreboque[anttdata] <br>$arrplacaterceiroreboque[anttusuario] </a></div></td>";
					
						$a.="	<td align=center ><div id='divplacaterceiroreboqueantt'></div>    <a href='#ancoraplacaterceiroreboque' class='btn btn-light btn-sm mb-1' class='btn btn-success btn-sm' onclick=\"window.open('../-/antt/antttela.php' ,'','width=1180,height=620,left=50,top=50')\">   <img src='../0bmp/$arrplacaterceiroreboque[ckantt].png' align='absmiddle' border='0' width='20' height='20'>          ANTT<br>$arrplacaterceiroreboque[anttdata] <br>$arrplacaterceiroreboque[anttusuario]</td></a></div>";
										
					}
							
					$a.="	<td align=center rowspan=2 width='15%'>  <a href='#ancoraplacaterceiroreboque' onclick=pesquisa('$arr[cpfcnpj]','$_GET[grupo]','$_GET[protocolo]','$_GET[codipessoa]','$_GET[placacarro]','$_GET[placareboque]','$_GET[placasemireboque]','$_GET[placaterceiroreboque]','$_GET[conta]','$_GET[contaprincipal]','consulta','$_GET[enviaemailresposta]')><div id='buttonaz'><img src='../0bmp/atualiza.png' width='20' height='20'  border='0' align='absmiddle'>Atualiza (F5)</div></a></td></td></tr>";

					$a .="</table>	";
										
			}
		}


		//***********************************************************************************************************		
		//** verifico aqui por ultimo a categoria da CNH validade da cnh
		//** para o operador nao errar se for carreta tem que ter a carteira E  ou
		//** OU TRUCK  ter a carteira C
		//****************************
		
		//verifico se tem placa e motorista, se tiver tem que avaliar a cnh
		//se tem codipessoa entao verifico a Categoria
		$arrpes['categoria'] = trim($arrpes['categoria']);
					
		// SE EXISTIR PESSOA  E EXISTIR UM VEICULO PELO MENOZ, PROCESSA A REQUISICAO
				
		//excluindo a nox
		if ( $arr['contaprincipal'] != 920771) {
		
		
			if (  	($_GET['codipessoa'] != '') &&
					(  ($_GET['placacarro']  != '') || ($_GET['placareboque']  != '') || ($_GET['placasemireboque']  != '') || ($_GET['placaterceiroreboque']  != '')  )	) {
				
	//echo "<br>codipessoa $arrpes[codipessoa]";		
		
				$fazerverificacao = false;	
				$msg = '';

				// se tem placa vai ter que ter uma categoria valida
				if (  $_GET['placacarro'] != '' ) {			


	//echo "<br>entrou $_GET[placacarro] != nulo  ";	


					if (  $arrpes['categoria'] == '' ) {  
			
			

	//echo "<br>entrou  $arrpes[categoria] ==  nulo ";	

			
						$letras = ['e','E'];	
						$fazerverificacao = true;	
						$msg = "CAT CNH - FAVOR INFORMAR CAMPO CATEGORIA CNH COMPATIVEL PARA CONDUZIR O VEICULO;";					

			
					} else {


	//echo "<br>else ---- $arrpes[categoria] == nulo ";	


						if ( ($arrplacacarro['categoria'] ==  'ARTICULADO' )    )  {

							// Letras que você deseja verificar
							$letras = ['e','E'];	
							$fazerverificacao = true;	
							$msg = "CAT CNH - VEICULO CADASTRADO COMO CATEGORIA ($arrplacacarro[categoria]) INCOMPATIVEL COM A CATEGORIA CNH ($arrpes[categoria]) INFORMADA, FAVOR CORRIGIR A CATEGORIA VEICULO OU CNH;";					

						} else if ( ($arrplacacarro['categoria'] ==  'TRUCK' )  ){
							
							$letras = ['c','C','d','D','e','E'];
							$fazerverificacao = true;					
							$msg = "CAT CNH - VEICULO CADASTRADO COMO CATEGORIA ($arrplacacarro[categoria]) INCOMPATIVEL COM A CATEGORIA CNH ($arrpes[categoria]) INFORMADA, FAVOR CORRIGIR A CATEGORIA VEICULO OU CNH;";					
							
						} else if (	($arrplacacarro['categoria'] ==  'MOTO' )  ){
								
							$letras = ['a','A'];	
							$fazerverificacao = true;		
							$msg = "CAT CNH - VEICULO CADASTRADO COMO CATEGORIA ($arrplacacarro[categoria]) INCOMPATIVEL COM A CATEGORIA CNH ($arrpes[categoria]) INFORMADA, FAVOR CORRIGIR A CATEGORIA VEICULO OU CNH;";					
								
						} else {
							
							//aqui vai incluir auto
							$letras = ['b','B','c','C','d','D','e','E'];	
							$fazerverificacao = true;					
							$msg = "CAT CNH - VEICULO CADASTRADO COMO CATEGORIA ($arrplacacarro[categoria]) INCOMPATIVEL COM A CATEGORIA CNH ($arrpes[categoria]) INFORMADA, FAVOR CORRIGIR A CATEGORIA VEICULO OU CNH;";					
							
						}
											
					}	
						
					
				} else if (  ($_GET['placareboque']  != '') || ($_GET['placasemireboque']  != '')   || ($_GET['placaterceiroreboque']  != '')   )  {

						// Letras que você deseja verificar
						$letras = ['E'];
						$fazerverificacao = true;					
						$msg = "CAT CNH - CATEGORIA VEICULO $_GET[placacarro] INCOMPATIVEL COM A CNH ($arrplacacarro[categoria]) INFORMADA, FAVOR CORRIGIR VEICULO/REBOQUE(S) OU CATEGORIA;";															
							
				}

	//echo "<br>mensagem pega : $msg<br>";		


				if ( $fazerverificacao) {

	//echo "<br>entro no if fazerverificacao $fazerverificacao ";	
					
					$encontrouCaracteres = false;

					foreach ($letras as $caractere) {
						if (strpos($arrpes['categoria'] , $caractere) !== false) {
							$encontrouCaracteres = true;
							break;
						}
					}

					if ( ! $encontrouCaracteres) {				
							

	//echo "<br>entrou no if condicao if ( ! $encontrouCaracteres) ";							
							
						// sistema bloqueia automatico cnh
						// logica: se a cnh for inconsistente bloqueia					
						$jatemaviso = strpos($arrpes['obs'], 'CAT CNH -');

						if ($jatemaviso == false) {
							

	//echo "<br>entrou no if do jatem aviso  $jatemaviso ";							
							
							$m  ="
								update tvalidapessoa
								set obs  = ('(".date('dmy')." ) ".$msg.";' || obs)
								where codipessoa = '$_GET[codipessoa]'  ";
									
							$res = pg_exec($m); 
							
							$sqlchamadaw = "
								update tchamada
								set resposta = ('$msg;' || resposta)
								where protocolo = '$_GET[protocolo]'  ";
									
							$res = pg_exec($sqlchamadaw);						
							
							$tvalida = "
								insert INTO tvalida (codipessoa,cpfcnpj,liberado,codimotivo,data,usuario,codiscore,score,obs) 
								VALUES ('$_GET[codipessoa]','$arrpes[cpfcnpj]','f','20','".date('d/m/Y')."','sis','20','CAT CNH','$msg')
								ON CONFLICT (codipessoa,codimotivo)
								DO update set liberado = 'f', data = '".date('d/m/Y')."',usuario = 'sis' 
							
							";		

							$restvalida = pg_exec($tvalida);						
							
							
							//tvalida 
							//motivo - codidescricao do motivo
							// motivo 20 - CATEGORIA CNH INCOMPATIVEL
							
							//codiscore  - score''
							//20			
							

						}		
					}


	/*
	echo "<pre>Descricao:
	codipessoa $_GET[codipessoa] 
	placa reboque $_GET[placareboque] 
	categoria cnh ($arrpes[categoria])
	categoria veiculo ($arrplacacarro[categoria])

	fazer verificacao $fazerverificacao 

	msg $msg

	posicao : $jatemaviso
	obs do arr : $arrpes[obs]

	sql tvalida  $tvalid
	sql validapessoa $m 
	sql chamada $sqlchamadaw
	sql valida $tvalida

	";*/	

					
				}				
			}		
		}
	
				
	} else {
	
		$a .= "<table>";
		$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Registro ja processado ou inexistente </td></tr>";
		$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Favor verificar se os parametros de pesquisa estao configurados corretamente ! </td></tr>";
		$a .= "</table>";
		
	}
	
	if ($_GET['verresposta'] == 'consulta') {
					
		$a .="<br><div align=center>  <a href='#liberacao' class='btn btn-success btn-lg mb-2' onclick=\"pesquisa('$arrpes[cpfcnpj]','$_GET[grupo]','$_GET[protocolo]','$_GET[codipessoa]','$_GET[placacarro]','$_GET[placareboque]','$_GET[placasemireboque]','$_GET[placaterceiroreboque]','$_GET[conta]','$_GET[contaprincipal]','visualisa','$_GET[enviaemailresposta]')\"> Visualizar resposta  </a></div>";			
		
	    echo "$a "; 
	
//echo "<br><br> emai resposta $_GET[enviaemailresposta]  <br>";

	
	} else {
		
	
			//*************************************
		// busco o rastreador para a servis CSN
		//*************************************
		$tiporastreamento = '';		
		//if ($arr['grupoprincipal'] == 'CSN' ) {						
			$sqlpegatiporastreamento = "
			
				select tiporastreamento 
				from tiporastreamento 
				where protocolo = $_GET[protocolo]
				
			";			
		//}	
		
		$restiporastreamento = pg_exec($sqlpegatiporastreamento);				
		if ( pg_numrows($restiporastreamento) > 0 ){
			$tiporastreamento = " Tipo Rastreamento: ".pg_result($restiporastreamento,'tiporastreamento');
		}	

	
	    // aqui nao mostra campos, ja mostra a resposta.
	
		$codresp = '';
		
    
		$codresp .= "<table border='0'  align=center ><tr><td>";
		$codresp .= "<table border='0'  align=center width='100%'>";
		$codresp .="<tr><td colspan='3' width='100%'><hr color='#000000' size='2' width='100%'></TD></tr>";


//echo "<br> print r".print_r($arrpes);
//echo "<br>$posmot == 't' && $poscarro == 't' && $posreb == 't' && $possemireb == 't' && $posterceiroreb == 't' ";
			


		
		if ($posmot == 't' && $poscarro == 't' && $posreb == 't' && $possemireb == 't' && $posterceiroreb == 't' )  {
			$codresp .="<tr><td> <b> $arr[maillogo] </td> <td  align='center' > <font size='4'> Situacao: APTO <BR> senha $arr[senha]</td><td  align='right'> Entrada $arr[dataentrada] <br>  sla:($arr[tempo])</td></tr>";
			$poschamada = 't';	
		}else{
			$codresp .="<tr><td > <b> </td> <td align='center' > <font size='4'> Situacao: PENDENTE REGULARIZACAO </td><td  align='right'> Entrada $arr[dataentrada] <br> sla:($arr[tempo]) </td></tr>";
			$poschamada = 'f';
		}
		
		$codresp .="<tr><td colspan='3' width='100%'><hr color='#000000' size='2' width='100%'></TD></tr>";

		$codresp .= "</table>";
		
		$codresp .="<table  border='0' align=center width='100%' >";
		$codresp .="<tr><td colspan=2> Comprovante de operacao pesquisa/consulta</td></tr>";
		$codresp .="<tr><td colspan=2> </td></tr>";
		$codresp .="</table>";

		$codresp .="<table   align=center >";
				
		$codresp .="<tr><td> Cliente:  </td><td> $arr[nomeconta]</td>";
		$codresp .="<tr><td> Conta:  </td><td> $arr[conta] - $tiporastreamento  </td>";
		$codresp .="<tr><td>   </td><td> </td>";
		
		$codresp .="<tr><td> 		  </td><td> Tipo: $arr[tipovinculo]</td>";
		if (strlen($_GET['codipessoa']) > 0 ) {
			$codresp .="<tr><td> Pessoa:  </td><td><b> $arrpes[cpfcnpj] - $arrpes[nomepessoa]</b></td>";
			$codresp .="<tr><td> 		  </td><td> Cat. CNH: $arrpes[categoria]</td>";
			
		}
		if (strlen($_GET['placacarro']) > 0 ) {
			$codresp .="<tr><td> Veiculo:  </td><td><b> $arrplacacarro[placa] </b> - $arrplacacarro[marca] $arrplacacarro[modelo]</td>";
			$codresp .="<tr><td>  		   </td><td> Prop: $arrplacacarro[nomepessoa] </td>";
		}	
		
		if (strlen($_GET['placasemireboque']) > 0 ) {
			$codresp .="<tr><td> SemiReboque:  </td><td> <b>$arrplacasemireboque[placa] </b>- $arrplacasemireboque[marca] $arrplacasemireboque[modelo]</td>";
			$codresp .="<tr><td>  		   </td><td> Prop: $arrplacasemireboque[nomepessoa] </td>";
		}	
		
		if (strlen($_GET['placaterceiroreboque']) > 0 ) {
			$codresp .="<tr><td> terceiroReboque:  </td><td> <b>$arrplacaterceiroreboque[placa] </b>- $arrplacaterceiroreboque[marca] $arrplacaterceiroreboque[modelo]</td>";
			$codresp .="<tr><td>  		   </td><td> Prop: $arrplacaterceiroreboque[nomepessoa] </td>";
		}	

		if (strlen($_GET['placareboque']) > 0 ) {
			$codresp .="<tr><td> Reboque:  </td><td> <b>$arrplacareboque[placa] </b>- $arrplacareboque[marca] $arrplacareboque[modelo]</td>";
			$codresp .="<tr><td>  		   </td><td> Prop: $arrplacareboque[nomepessoa] </td>";
		}	
		
		$codresp .="<tr><td>   </td><td> </td>";
		
		// se foi liberado
		if ($poschamada == 't') {
		
			// entao verifico se um motorista e terceiro
			if ($arr['tipovinculo'] == 'AUTONOMO' || 
					$arr['tipovinculo'] == 'AJUDANTE' || 
					$arr['tipovinculo'] == 'TERCEIRO180' ) {
												
				// se for terceiro dou 1 dia de vigencia

				$_GET['grupo'] = str_replace(' ', '',$_GET['grupo']);
				
				// se � terceiro existe uma regra especifica para 3 coracoes
								
				$intconta = (int)$arr['conta'];

                $datavalidadeconsulta = strtotime($arr['dataentradacalculo']);                               
                $datavalidadeconsulta = strtotime("+$arr[diasvigenciaconsulta] days", $datavalidadeconsulta);
                
                if ($arr['diasvigenciaconsulta'] == 1) 
                    $codresp .="<tr><td> Vigencia:  </td><td> Liberado para 1 (um) carregamento </td>";
                else
					$codresp .="<tr><td> Vigencia:  </td><td> ".date('d/m/Y',$datavalidadeconsulta)." </td>";
								
			// Servis Grupo CSN,terceiro pode ser liberado por 1 semana
			} else if ($arr['tipovinculo'] == 'SEMANAL' ) {
	
                $codresp .="<tr><td> Vigencia:  </td><td> Liberado por 7 (Sete) dias </td>";

			// SE FUNCIONARIO,AGREGADO,TERCEIRO180		
			} else {
				
				// Servis Grupo CSN,terceiro pode ser liberado por 180 DIAS (VICULO TERCEIRO180				
				$codresp .="<tr><td> Vigencia:  </td><td> $arr[validade]</td>";
			}	
            
            
			
		}else {
		
			//obs			
			$auxobs = str_replace(";","<br>",$arr['resposta']);
			
			$codresp .="<tr><td>Obs.:</td><td> $auxobs </td>";

		}
        
        //***************************
        //** obsresposta para serasa 100 ou 200 mil
        //***************************
        $codresp .="<tr><td><br>Obs.:</td><td> $arr[obsresposta] </td>";
    
	

	//verifica se � sada carreteiro
		if (  $arr['conta'] == 877960 || $arr['conta'] == 877961 || $arr['conta'] == 877962	|| $arr['conta'] == 877963  ) {
		
			$sql = "
			update tchamada
			set  resposta = ('Liberacao para frota Cegonha SADA'  || resposta)
			where  protocolo = '$_GET[protocolo]'  ";
					
			$res = pg_exec($sql);
		
			$codresp .="<tr><td>   </td><td> Liberacao para frota Cegonha SADA </td>";
		}
		
		$codresp .="<tr><td> Autenticacao:  </td><td> ".md5($arr['senha'])." - Layout:7856b</td>";
		
		$codresp .="<tr><td colspan='3' width='100%'><hr color='#000000' size='2' width='100%'></TD></tr>";
		$codresp .="<tr><td colspan=2 class='letra_gris'>Documento confidencial e interno, sua divulga&ccedil;&atilde;o poder&aacute; implicar em responsabilidade!</td>";
		$codresp .="<tr><td colspan=2 class='letra_gris'>Contratar ou n&atilde;o servi&ccedil;o(s) deste perfil &eacute; uma decis&atilde;o exclusivamente tomada pela cliente ou empresa consultante !</td>";
		
		if ( $arr['contaprincipal'] == '920771') {
			$codresp .="<tr> <td colspan=2> <br> Pacote integracao $arr[pacote]</td></tr>";				
		}

		$codresp .="</table>";
		$codresp .="<A NAME='liberacao'></A>";
		$codresp .="</table> </td></tr>";	
		
		
		
		// eu coloco uma seringa no meio pq o asp nao passa por referencia espacos em branco
		
		//$datadesaida = substr(" ","*",$datadesaida);
		
		if ( $_GET['verresposta'] == 'visualisa' ) {
				
			//********************************************
			//********************************************			
			// codigo transferido para ../-/interrisco/criarespsta.php			
			//********************************************
			//********************************************
		
		    // regra par a nox
			// auqi coloco uma regra, para criar uma substatus
			// tipo substatos liberado, pendente, negativado
			// se for substatus pendente, a nox vai mandar documento, isso � para controle deles
			
			if ($poschamada == 'f') {
						
				//920771 conta nox	
				if ($_GET['contaprincipal'] == '920771') {
			
					$codresp .=  "<BR><table align=center><tr><td class=menuiz_botonoff align=center><fieldset><legend><b>Atencao!! </b></legend>  <img src='../0bmp/interrogacao2.png'  width='45' height='35'>  <h3> Para a NOX, se nao der para liberar (  <a class='btn btn-info btn-sm' href='#' role='button' onclick='substatusnox($_GET[protocolo])'> CLIQUE AQUI </a> )</h3></fieldset></td></tr></table>";
					
				}						
			}	
					
			$operacaoacocearense = false;
			$a = '';			
			
			if (mb_strpos($arr['nomeconta'], "CEARENSE") !== false) {
				$operacaoacocearense = true;
			}
			if (mb_strpos($arr['nomeconta'], "SINOBRAS") !== false) {
				$operacaoacocearense = true;
			}
			
			//se for acocearense ou sinobras aplicar as tregas
			if ( 	$operacaoacocearense  ) {
								

				$autenticacao=md5($_GET['senha']);

				//gambiarra para aproveitar o codigo

				if ( $posmot == 't' && 
					$poscarro == 't' && 
					$posreb == 't' && 
					$possemireb == 't' && 
					$posterceiroreb == 't' )  {

					$poschamada = 't';

				}else{
					
					$poschamada = 'f';
						
				}	

				$a .=  "<br>

				<div class='row' >											
					
					<div class='col-sm-8' align='center'>
						
						<p  class='btn btn-success btn-sm mb-2' onclick=\"window.open('../-/interrisco/criaresposta.php?"
						."&etapa=visualisa"
						."&protocolo=$_GET[protocolo]"					
						."&grupo=$_GET[grupo]"			
						."&email=$arr[email]"
						
						."&codipessoa=$arrpes[codipessoa]"
						."&conta=$_GET[conta]"
						."&contaprincipal=$_GET[contaprincipal]"
												
						."&posmot=$posmot"
						."&poscarro=$poscarro"
						."&posreb=$posreb"
						."&possemireb=$possemireb"
						."&posterceiroreb=$posterceiroreb"
						."&poschamada=$poschamada" 
												
						."&tiporastreamento=$tiporastreamento"
										
						."&maillogo=$arr[maillogo]"				
						."&dataentrada=$arr[dataentrada]"
						."&tempo=$arr[tempo]"
						."&nomeconta=$arr[nomeconta]"
						."&conta=$arr[conta]"
						."&tipovinculo=$arr[tipovinculo]"
						."&cpfcnpj=$arrpes[cpfcnpj]"
						."&motorista=$arrpes[nomepessoa]"
						."&categoria=$arrpes[categoria]"
						
						."&placacarro=$arrplacacarro[placa]"
						."&marcacarro=$arrplacacarro[marca]"				
						."&modelocarro=$arrplacacarro[modelo]"				
						."&nomepessoacarro=$arrplacacarro[nomepessoa]"
						."&datacriacaoanttcarro=$arrplacacarro[datacriacao]"

						."&placareboque=$arrplacareboque[placa]"
						."&marcareboque=$arrplacareboque[marca]"				
						."&modeloreboque=$arrplacareboque[modelo]"				
						."&nomepessoareboque=$arrplacareboque[nomepessoa]"
						."&datacriacaoanttreboque=$arrplacacarro[datacriacao]"

						."&placasemireboque=$arrplacasemireboque[placa]"
						."&marcasemireboque=$arrplacasemireboque[marca]"				
						."&modelosemireboque=$arrplacasemireboque[modelo]"				
						."&nomepessoasemireboque=$arrplacasemireboque[nomepessoa]"
						."&datacriacaoanttsemireboque=$arrplacacarro[datacriacao]"

						."&placaterceiroreboque=$arrplacaterceiroreboque[placa]"
						."&marcaterceiroreboque=$arrplacaterceiroreboque[marca]"				
						."&modeloterceiroreboque=$arrplacaterceiroreboque[modelo]"				
						."&nomepessoaterceiroreboque=$arrplacaterceiroreboque[nomepessoa]"
						."&datacriacaoanttterceiroreboque=$arrplacacarro[datacriacao]"
						
						."&validade=$arr[validade]"
						."&resposta=$arr[resposta]"
						."&obsresposta=$arr[obsresposta]"
						."&pacote=$arr[pacote]"
						."&autenticacao=$autenticacao"	
						."&fazerregrasaco=t"				
						
						."','','toolbar=no, location=no, directories=no, status=no, menubar=no,width=1180,height=620,left=0,top=0'); \"> Grava Resposta ( nova tela )  Aplicar regras ANTT ACO    </p>

					</div>";
				
				
				
				$a .=  "
				
					<div class='col-sm-2' align='center'>
						
						<p  class='btn btn-light btn-sm mb-2' onclick=\"window.open('../-/interrisco/criaresposta.php?"
						."&etapa=visualisa"
						."&protocolo=$_GET[protocolo]"					
						."&grupo=$_GET[grupo]"			
						."&email=$arr[email]"
						
						."&codipessoa=$arrpes[codipessoa]"
						."&conta=$_GET[conta]"
						."&contaprincipal=$_GET[contaprincipal]"
												
						."&posmot=$posmot"
						."&poscarro=$poscarro"
						."&posreb=$posreb"
						."&possemireb=$possemireb"
						."&posterceiroreb=$posterceiroreb"
						."&poschamada=$poschamada" 
												
						."&tiporastreamento=$tiporastreamento"
										
						."&maillogo=$arr[maillogo]"				
						."&dataentrada=$arr[dataentrada]"
						."&tempo=$arr[tempo]"
						."&nomeconta=$arr[nomeconta]"
						."&conta=$arr[conta]"
						."&tipovinculo=$arr[tipovinculo]"
						."&cpfcnpj=$arrpes[cpfcnpj]"
						."&motorista=$arrpes[nomepessoa]"
						."&categoria=$arrpes[categoria]"
						
						."&placacarro=$arrplacacarro[placa]"
						."&marcacarro=$arrplacacarro[marca]"				
						."&modelocarro=$arrplacacarro[modelo]"				
						."&nomepessoacarro=$arrplacacarro[nomepessoa]"
						."&datacriacaoanttcarro=$arrplacacarro[datacriacao]"

						."&placareboque=$arrplacareboque[placa]"
						."&marcareboque=$arrplacareboque[marca]"				
						."&modeloreboque=$arrplacareboque[modelo]"				
						."&nomepessoareboque=$arrplacareboque[nomepessoa]"
						."&datacriacaoanttreboque=$arrplacacarro[datacriacao]"

						."&placasemireboque=$arrplacasemireboque[placa]"
						."&marcasemireboque=$arrplacasemireboque[marca]"				
						."&modelosemireboque=$arrplacasemireboque[modelo]"				
						."&nomepessoasemireboque=$arrplacasemireboque[nomepessoa]"
						."&datacriacaoanttsemireboque=$arrplacacarro[datacriacao]"

						."&placaterceiroreboque=$arrplacaterceiroreboque[placa]"
						."&marcaterceiroreboque=$arrplacaterceiroreboque[marca]"				
						."&modeloterceiroreboque=$arrplacaterceiroreboque[modelo]"				
						."&nomepessoaterceiroreboque=$arrplacaterceiroreboque[nomepessoa]"
						."&datacriacaoanttterceiroreboque=$arrplacacarro[datacriacao]"
						
						."&validade=$arr[validade]"
						."&resposta=$arr[resposta]"
						."&obsresposta=$arr[obsresposta]"
						."&pacote=$arr[pacote]"
						."&autenticacao=$autenticacao"	
						."&fazerregrasaco=f"				
						
						."','','toolbar=no, location=no, directories=no, status=no, menubar=no,width=1180,height=620,left=0,top=0'); \"> Ignorar regras aco    </p>

					</div>";
				
					
					
				$a .=  "	
					<div class='col-sm-2' align='center'>
						
						<p  class='btn btn-light btn-sm mb-2' onclick=\"window.open('../-/interrisco/alterarespostapesquisa.php?&tipo=resposta&protocolo=$arr[protocolo]&obs=$arr[resposta]','','toolbar=no, location=no, directories=no, status=no, menubar=no,width=1180,height=620,left=0,top=0'); \"> Edita Resposta    </p>
					
					</div></div>";

//					<a href=' target = '_blank' ><div id='buttonaz'><img src='../0bmp/interno.gif' width='25' height='25'  border='0' align='absmiddle'> Edita resposta</DIV></a>
			

//echo "<br><br> emai resposta $_GET[enviaemailresposta]  <br>";
			
			} else {
				
				// senão libera o bota pesquisa
				
				$a .="<BR><table border='0'  align=center  >
						<tr class='botonmoduleon'>
							<td><a href='../-/interrisco/alterarespostapesquisa.php?&tipo=resposta&protocolo=$arr[protocolo]&validade=$arr[validade]&resposta=$arr[resposta]&tipovinculo=$arr[tipovinculo]&codipessoa=$_GET[codipessoa]&placacarro=$_GET[placacarro]&placareboque=$_GET[placareboque]&placasemireboque=$_GET[placasemireboque]&placaterceiroreboque=$_GET[placaterceiroreboque]' target = '_blank' ><div id='buttonaz'><img src='../0bmp/interno.gif' width='25' height='25'  border='0' align='absmiddle'> Edita resposta </DIV></a></td>
							<td><a href='#' onclick=\"gravacaochamada('$_GET[grupo]','$poschamada','$posmot','$poscarro','$posreb','$possemireb','$posterceiroreb','$datadesaida','$horadesaida','$arr[protocolo]','$_GET[codipessoa]','$_GET[placacarro]','$_GET[placareboque]','$_GET[placasemireboque]','$_GET[placaterceiroreboque]','$_GET[conta]','$_GET[contaprincipal]','finalresposta','','')\"><div id='buttonaz'> <img src='../0bmp/clipresp.png' width='25' height='25'  border='0' align='absmiddle'> Grava Resposta (modo antigo) </div></td>			
							<td><input type='button' class='botaop_sombra' value='Grava com nova vigencia' onclick=gravacaochamada('$_GET[grupo]','$poschamada','$posmot','$poscarro','$posreb','$possemireb','$posterceiroreb','$datadesaida','$horadesaida','$arr[protocolo]','$_GET[codipessoa]','$_GET[placacarro]','$_GET[placareboque]','$_GET[placasemireboque]','$_GET[placaterceiroreboque]','$_GET[conta]','$_GET[contaprincipal]','finalresposta',$vigencia,$custopesquisa)></td>			

						</tr>	
						
					</table>
				";		 
						
			}			
									
			$codresp .= $a;	 

		} else if ($_GET['verresposta'] == 'finalresposta') {

			//****************************************
			//* grava ck pessoa
			//****************************************
			if (strlen($_GET['codipessoa']) > 0 ) {
									
				$sqlck = " 
					update tvalidapessoa 
					set ck = '$posmot'
					where codipessoa = '$_GET[codipessoa]' ";
				
				$resck = pg_exec($sqlck);
			}
	
			//****************************************
			//* grava ck carro
			//****************************************
			if (strlen($_GET['placacarro']) > 0 ) {
									
				$sqlck = " 
					update tvalidaplaca 
					set ck = '$poscarro'
					where placa = '$_GET[placacarro]' ";
				
				$resck = pg_exec($sqlck);
			}
		
			//****************************************
			//* grava ck reboque
			//****************************************
			if (strlen($_GET['placareboque']) > 0 ) {
									
				$sqlck = " 
					update tvalidaplaca 
					set ck = '$posreb'
					where placa = '$_GET[placareboque]' ";
				
				$resck = pg_exec($sqlck);
			}
		
			//****************************************
			//* grava ck semi reboque
			//****************************************
			if (strlen($_GET['placasemireboque']) > 0 ) {
									
				$sqlck = " 
					update tvalidaplaca 
					set ck = '$possemireb'
					where placa = '$_GET[placasemireboque]' ";
				
				$resck = pg_exec($sqlck);
			}		
			
			
			//****************************************
			//* grava ck terceiro reboque
			//****************************************
			if (strlen($_GET['placaterceiroreboque']) > 0 ) {
									
				$sqlck = " 
					update tvalidaplaca 
					set ck = '$posterceiroreb'
					where placa = '$_GET[placaterceiroreboque]' ";
				
				$resck = pg_exec($sqlck);
			}
			
			
			//*********************************************************
			// verifico se preciso renovar vigencia, quando o cliente
			// manda a ficha antes do prazo e quer renovar mesmo assim
			// em vez de fazer consulta cobra renovacao
			//*********************************************************
			
			$sqlvigencia = '';
			
			if ($_GET['vigencia'] != '') {
			
				$vigencia = (int)$_GET['vigencia'];	
				$validade = date("d/m/Y", strtotime("+$vigencia days")); 
				
				$arr['validade'] = $validade; 
				
				$sqlvigencia = "validade = '$validade',
								custo = '$_GET[custopesquisa]',
								pescon = 'REN',	";
			
			}
			
			//****************************************
			//* grava resposta
			//****************************************
			
			$sql = "
			update tchamada
			set  datasaida = '$_GET[datasaida] $_GET[horadesaida]' ,
				liberado =    '$_GET[poschamada]' ,					
				$sqlvigencia
				usuarioquefezaresposta  = '$_SESSION[usuario]',
				statusprotocolo = '10'
			where  protocolo = '$_GET[protocolo]'  ";
					
			$res = pg_exec($sql);

			// salva resposta		
			
//			$z = "codipessoa $_GET[codipessoa]  placa carro $_GET[placacarro] paca reboque $_GET[placareboque] placa semi reboque$_GET[placasemireboque] 1 $posmot 2 $poscarro 3 $posreb 4 $possemireb'";
			
			echo "<div class='alert alert-success' role='alert'>Resposta salva com sucesso !</div>";

										
			if ($_GET['vigencia'] != '') {
				
				$codresp .="<table  width='30%' border='1'  align=center  >";	
				$codresp .="<tr><td class='redonda'> Vigencia atualizada para: $validade   <td></tr>";			
						
			}
						
			$codresp .="<br><div class='alert alert-success' role='alert'>Resposta salva com sucesso !</div>";
						
			//nox
			//enviaemailresposta
//echo "<br><br> emai resposta $_GET[enviaemailresposta]  <br>";
			
			if ($_GET['enviaemailresposta'] == 't') {
				
				$codresp .="<br><div class='alert alert-danger' role='alert'> cliente solicita que envie email</div>";
				
			}
						
			//se o cliente nao for nox pode mandar email
			//$codresp .="<tr><td>$email</td></tr></table>";			
			//$codresp .="<br><table  width='30%' border='0'  align=center  >";
			//$codresp .="<tr><td><input type='submit' class='botao' value='Enviar Email' name='submit' id='submit' onclick=emailrespostaconsulta('$arr[protocolo]','$email')></td></tr>";							
			//$codresp .="</table><br>";

			$email = strtolower ($arr['email']);
			//$codresp .="<table width='100%' class='tabla_cabecera' align=center ><tr><td>Email para: ";
			$email=  preg_split ('/[\s,;]+/', $email);

			$codresp .="<div class='alert alert-light' role='alert'>Email para: $arr[email] </div>";

			//var_dump($dest); visualiza array

			for ($i = 0; $i < count($email); $i++) {

				//$codresp .=$email[$i]." ";
			
			}
			//$codresp .="</table width='100%' class='tabla_cabecera' align=center ><tr><td>Email para: ";

			// emailrespostaconsulta( declarado em 0interrisco/registrospendentes
			$codresp .="  <div align=center><a  class='btn btn-info btn-md' href='#' role='button' onclick=\"onclick=emailrespostaconsulta('$arr[protocolo]','$email')\"> Clique aqui para enviar o email </a></div>";
									
			//***
			//*** resposta do registros pendentes
			//***
			
			$grupoprincipal = trim($arr['grupoprincipal']);
										
			//*************
			//	controle replicacao
			// shave shuttle
			//*************
			
			//servidor contingencia
			//186.231.33.58
			//ip quente 186.225.18.161
			
			if ($arr['cnpjglog'] != '') {
				
				//servis csn
				if ( $arr['grupoprincipal'] == 'CSN' ) {
					echo replicacao($grupoprincipal,$arr['protocolo'],$arr['cnpjglog'],$arr['senhaglog'],'csn.servisgr.com.br:12126');
					//echo replicacao($grupoprincipal,$arr['protocolo'],$arr['cnpjglog'],$arr['senhaglog'],'186.225.18.161:12126');

				//servis
				} else if ( $arr['contaprincipal'] == '855705' ) { 								
					echo replicacao($arr['grupo'],$arr['protocolo'],$arr['cnpjglog'],$arr['senhaglog'],'csn.servisgr.com.br:12121');    
				}	
//				else if ( $arr[contaprincipal] == '908311' ) //cci
//					echo replicacao($grupoprincipal,$arr[protocolo],$arr[cnpjglog],$arr[senhaglog],'189.17.157.85:12121');
		 
            }
					
		}		  
		
		echo "<br> $codresp "; 

	}
	
//echo 				 " Vigencia:  ".date("d/m/Y", strtotime("+30 days"))."";
	
	echo ftela('tela1k2m');


		
//******************************
// Envia email para consultoria criminal
// envia email perfil dtpr kadi
//******************************
} else if ( $_GET['sq']	== 'emailconsultoria' ) {

	$sqlpes = "			
		select 
			nomepessoa,
			tpessoa.cpfcnpj,
			uf,			
			rg,
			ufrg,
			ufnascimento,
			cidadenascimento,
			dtnascimento,
			nomepai,
			nomemae
		from
			tpessoa,
			tpessoafisica
			
		where tpessoa.codipessoa = $_GET[codipessoa] and
			tpessoa.codipessoa = tpessoafisica.codipessoa ";

	$respes = pg_exec($sqlpes);			
	$arrpes = pg_fetch_array($respes,0,PGSQL_ASSOC);

	// aqui eu testo se da para liberar o motorista ou nao.			
	// se o ck for diferente de verdadeiro posmot sera falso
		
	$a ="<table class='tabla_cabecera' border='0'  align=center width='100%' >";
		$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=4> Pesquisa - perfil social (PS)</td></tr>";
		$a .="<tr><td> CPF: </td><td>  $arrpes[cpfcnpj] </td></tr>";
		$a .="<tr><td> Nome: </td><td>  $arrpes[nomepessoa] </td></tr>";
		$a .="<tr><td> RG: </td><td> $arrpes[rg] - $arrpes[ufrg]   </td>";		
		$a .="    <td> Nasc </td><td> $arrpes[dtnascimento] $arrpes[cidadenascimento] - $arrpes[ufnascimento] </td></tr>";
		$a .="<tr><td> Pai </td><td> $arrpes[nomepai]</td>";
		$a .="	<td> Mae </td><td> $arrpes[nomemae]</td></tr>";				
	$a .="</table>";


	//** envia email servidor remoto
	//	echo "<h2> EMAIL ENVIADO PELA CONTA interrisco2@gmail.com</H2>";
	// 	echo $a;	
		
	//	require_once('C:/xampp/htdocs/-/funcoes/emailviacurl.php');
	//	echo emailviacurl('smtp.gmail.com','interrisco2@gmail.com','putm rgkm hnyd pfqp',"$_GET[emailpara]","$arrpes[cpfcnpj] $arrpes[nomepessoa] CONVENCIONAL","$a",'interrisco@gmail.com');


	// envio email para o civil consultoria 
			
/*
	$sql = "			
		select 
			senha,
			host,
			usuario
		from
			temail			
		where codiemail = 'civil' ";

	$resp = pg_exec($sql);			
	
	
	if ( pg_num_rows($resp) > 0 ){	
    
		$parametros = pg_fetch_array($resp,0,PGSQL_ASSOC);
			
	}
	
	//$_GET['emailpara'] = 'worklord@gmail.com';
	*/
	
	//**************************************
	//*	envio de email pelo phpmailer 5.2.26
	//**************************************

	//echo "$sql <br>";
	//echo "usuario $parametros[usuario] <br>";
	//echo "senha $parametros[senha] <br>";

	require_once('../-/funcoes/enviaemail.php');	
				
	//envio de email com servidor local // funcao esta em /-/funcoes/enviaemail.php
	$host = 'smtp.gmail.com';
	//$usuario = 'mail@interrisco.com.br';
	$usuario = 'interrisco@gmail.com';
	$senha = 'tlva kijy irbu vyid';
	$destino = $_GET['emailpara'];
	$assunto = $arrpes['nomepessoa'];
	$msg = $a;
	$replicapara = '';

	echo enviaemailreplica($host,$usuario,$senha,$destino,$assunto,$msg,$replicapara);

	
//******************************
// enviar email de resposta
//******************************
} else if ( $_GET['sq']	== 'emailrespostaconsulta' ) {

  echo emailrespostaconsulta($_GET['protocolo']);
	
//************************************
//* SALVA VALIDACOES EM TVALIDAPESSOA
//************************************
//serve para gravar as validacoes feitas de consultoria,cnh, ccf do motorista

} else if ( $_GET['sq']	== 'gravavalidapessoa' ) {

	//tipo =  informa qual div vai aparecer a figura do positivo ou negativo
	//situacao =  t ou f, se ta liberado ou nao
	//codipessoa = o motorista
			
	$sql = " update tvalidapessoa ";
	
	if ($_GET['tipo'] == 'divreceita') {
	
		$sql .= "set ckreceita = '$_GET[situacao]',
					receitadata = '".date('d/m/Y')."',
					receitausuario = '$_SESSION[usuario]' ";
					
	} else if ($_GET['tipo'] == 'divconsultoria') {
	
		$sql .= "set ckconsultoria = '$_GET[situacao]',
					ckdata = '".date('d/m/Y')."',
					consultoriadata = '".date('d/m/Y')."',
					consultoriausuario = '$_SESSION[usuario]' ";
					
	} else if ($_GET['tipo'] == 'divtj') {
	
		$sql .= "set cktj = '$_GET[situacao]',
					tjdata = '".date('d/m/Y')."',
					tjusuario = '$_SESSION[usuario]' ";				
			
	} else if ($_GET['tipo'] == 'divcnh') {
	
		$sql .= "set ckcnh = '$_GET[situacao]',					
					cnhdata = '".date('d/m/Y')."',
					cnhusuario = '$_SESSION[usuario]' ";
	
	} else if ($_GET['tipo'] == 'divcomercial') {
	
		$sql .= "set ckcheque = '$_GET[situacao]' ,					
					chequedata = '".date('d/m/Y')."',
					chequeusuario = '$_SESSION[usuario]' ";
		
	} else if ($_GET['tipo'] == 'divserasa') {
	
		$sql .= "set ckserasa = '$_GET[situacao]' ,					
					serasadata = '".date('d/m/Y')."',
					serasausuario = '$_SESSION[usuario]' ";
		
	} else if ($_GET['tipo'] == 'divfonemot') {
	
		$sql .= "set ckfone = '$_GET[situacao]' ,					
					fonedata = '".date('d/m/Y')."',
					foneusuario = '$_SESSION[usuario]' ";
					
	} else if ($_GET['tipo'] == 'divexperienciamotorista') {
	
		$sql .= "set experienciack = '$_GET[situacao]' ,					
					experienciadata = '".date('d/m/Y')."',
					experienciausuario = '$_SESSION[usuario]' ";	

	} else if ($_GET['tipo'] == 'divfacial') {
	
		$sql .= "set ckfacial = '$_GET[situacao]' ,					
					facialdata = '".date('d/m/Y')."',
					facialusuario = '$_SESSION[usuario]' ";	

	} else if ($_GET['tipo'] == 'divrdomotorista') {
	
		//atualiza socre ck rdo
		
		$sql .= "set ckrdo = '$_GET[situacao]' ,					
					rdodata = '".date('d/m/Y')."',
					rdousuario = '$_SESSION[usuario]' ";
					
	}		
	
	$sql .= "where codipessoa = '$_GET[codipessoa]' ";
	
	$res = pg_exec($sql);
	
	if ($res) {
	  echo "<img src='../0bmp/t.png' align='absmiddle' border='0' width='10' height='10'> Salvo com sucesso ! ";
	} else {
	  echo "Nao foi possivel efetuar gravacao ! <br> codigo do erro: $res ";
	}	
		
	
	//******************
	// gravo estatistica de operador
	//******************
	
	$selectclique = "select 
						to_char((current_timestamp - data), 'HH24:MI:SS') as temposemclique
					from tloginclique 
					where login = '$_SESSION[usuario]' 						
					order by id desc
					limit 1 					";
						
	$resstprot = pg_exec($selectclique);
	$temposemclique = pg_result($resstprot,'temposemclique');
		
	if ($temposemclique == '')
		$temposemclique = '00:00:00';	
		
	$sqlins = " insert into tloginclique (  login,data,tempo,codipessoa)
		values('$_SESSION[usuario]','".date('d/m/Y H:i:s')."','$temposemclique','$_GET[codipessoa]')  ";

	$res = pg_exec($sqlins);
	
	
//$f = fopen('../log.txt', 'w'); fwrite($f,"\n SELECTBUSCA \n $selectclique  \n INSERT \n $sqlins" ); fclose($f);	

	
	
//****************************************
//* SALVA OBSERVACOES EM TVERIFICA PESSOA
//***************************************
// salva observacoes da pesquisa do motorista
} else if ( $_GET['sq']	== 'gravavalidapessoaobs' ) {

	// codipessoa = o motorista
	// obs = observacao
	
	// se o protocolo � diferente de vazio entao grava a obs em tchamada tb.
	
	$_GET['obs'] = RemoveAcentos(strtoupper($_GET['obs']));
	
	
	if ($_GET['obs'] != '') {
		$sql = "
			update tvalidapessoa 
			set obs = ('(".date('dmy')." $_SESSION[usuario]) $_GET[obs];' || obs)
			where codipessoa = '$_GET[codipessoa]'  ";
					
		$res = pg_exec($sql);

		
		
		
		if ($_GET['protocolo'] <> '') {
			
			$stprot = "select statusprotocolo from tchamada where protocolo = '$_GET[protocolo]'  "; 
			$resstprot = pg_exec($stprot);
			$stprot = (int)pg_result($resstprot,'statusprotocolo'); 
			
			if ($stprot < 10) {
			    $stprot = ($stprot+1);
			}else  { 
				$stprot = 10;
			}
			
			$sqlchamada = "
				update tchamada
				set statusprotocolo = $stprot,
					resposta = ('* $_GET[obs];' || resposta)
				where protocolo = '$_GET[protocolo]'  ";
						
			$reschamada = pg_exec($sqlchamada);
		}	

		if ($res) {
		  echo "<img src='../0bmp/t.png' align='absmiddle' border='0' width='10' height='10'> Salvo com sucesso ! ";
		} else {
		  echo "Nao foi possivel efetuar gravacao ! <br> codigo do erro: $res ";
		}	
	}
//************************************
//* SALVA VALIDACOES EM treferencia
//************************************
//serve para gravar as validacoes das referencias
} else if ( $_GET['sq']	== 'gravavalidaref' ) {

	//situacao =  t ou f, se ta liberado ou nao
	//codireferencia = a referencia
	
	$sql = "
		update treferencia
		set ck = '$_GET[situacao]',
			ckdata = '".date('d/m/y')."' 
		where codireferencia = '$_GET[codireferencia]'  ";
		
	$res = pg_exec($sql);

	if ($res) {
	  echo "<img src='../0bmp/t.png' align='absmiddle' border='0' width='10' height='10'> Salvo com sucesso ! ";
	} else {
	  echo "Nao foi possivel efetuar gravacao ! <br> codigo do erro: $res ";
	}
//****************************************
//* SALVA OBSERVACOES  EM TREFERENCIA
//***************************************
// salva observacoes da pesquisa das referencias
} else if ( $_GET['sq']	== 'gravavalidaobsref' ) {

	// tipo =  informa qual div vai aparecer a figura do positivo ou negativo
	// situacao =  t ou f, se ta liberado ou nao
	// codipessoa = o motorista
	// obs = observacao
	
	
	$_GET['obs'] = RemoveAcentos(strtoupper($_GET['obs']));
	
	if ($_GET['obs'] != '') {
		
		$sql = "
			update treferencia
			set obs  = ('(".date('dmy')." $_SESSION[usuario]) $_GET[obs];' || obs)
			where codireferencia = '$_GET[codireferencia]'  ";
					
		$res = pg_exec($sql);
		
		// se protocolo for diferente de nulo entao grava na chamada, senao sograva em treferencia
		if ($_GET['protocolo'] != '') {
		
			$stprot = "select statusprotocolo from tchamada where protocolo = '$_GET[protocolo]'  "; 
			$resstprot = pg_exec($stprot);
			$stprot = (int)pg_result($resstprot,'statusprotocolo'); 
			
			if ($stprot < 10) {
			    $stprot = ($stprot+1);
			}else  { 
				$stprot = 10;
			}
		
		
			$sqlchamada = "
				update tchamada
				set statusprotocolo = $stprot,
					resposta = ('* $_GET[obs];' || resposta)
				where protocolo = '$_GET[protocolo]'  ";
						
			$reschamada = pg_exec($sqlchamada);
		}
		
		if ($res) {
		  echo "<img src='../0bmp/t.png' align='absmiddle' border='0' width='10' height='10'> Salvo com sucesso ! ";
		} else {
		  echo "Nao foi possivel efetuar gravacao ! <br> codigo do erro: $res ";
		}
	}
//
//echo $sql;

//************************************
//* SALVA VALIDACOES EM tvalida placa
//************************************
//serve para gravar as validacoes de placa
} else if ( $_GET['sq']	== 'gravavalidaplaca' ) {

	//situacao =  t ou f, se ta liberado ou nao
	//codireferencia = a referencia
	
	$sql = " update tvalidaplaca ";
	
	if ($_GET['tipo'] == 'divplacacarrolicenciamento' || $_GET['tipo'] == 'divplacareboquelicenciamento' || $_GET['tipo'] == 'divplacasemireboquelicenciamento' || $_GET['tipo'] == 'divplacaterceiroreboquelicenciamento') {
	
		$sql .= "set cklicenciamento = '$_GET[situacao]' ,
				ckdata = '".date('d/m/Y')."',
				licenciamentodata = '".date('d/m/Y')."',
				licenciamentousuario = '$_SESSION[usuario]' ";
				
	} else if ($_GET['tipo'] == 'divplacacarroantt' || $_GET['tipo'] == 'divplacareboqueantt' || $_GET['tipo'] == 'divplacasemireboqueantt' || $_GET['tipo'] == 'divplacaterceiroreboqueantt') {
	
		$sql .= "set ckantt = '$_GET[situacao]' ,
				anttdata = '".date('d/m/Y')."',
				anttusuario = '$_SESSION[usuario]' ";

				
	} else if ($_GET['tipo'] == 'divplacacarropropriedade' || $_GET['tipo'] == 'divplacareboquepropriedade' || $_GET['tipo'] == 'divplacasemireboquepropriedade' || $_GET['tipo'] == 'divplacaterceiroreboquepropriedade') {
	
		$sql .= "set ckpropriedade = '$_GET[situacao]' ,
				propriedadedata = '".date('d/m/Y')."',
				propriedadeusuario = '$_SESSION[usuario]' ";
			
	} else if ($_GET['tipo'] == 'divplacacarrocheque' || $_GET['tipo'] == 'divplacareboquecheque' || $_GET['tipo'] == 'divplacasemireboquecheque' || $_GET['tipo'] == 'divplacaterceiroreboquecheque') {
	
		$sql .= "set ckcheque = '$_GET[situacao]',
				chequedata = '".date('d/m/Y')."',
				chequeusuario = '$_SESSION[usuario]' ";
	
	} else if ($_GET['tipo'] == 'divplacacarrofone' || $_GET['tipo'] == 'divplacareboquefone' || $_GET['tipo'] == 'divplacasemireboquefone' || $_GET['tipo'] == 'divplacaterceiroreboquefone') {
	
		$sql .= "set ckfone = '$_GET[situacao]' ,
				fonedata = '".date('d/m/Y')."',
				foneusuario = '$_SESSION[usuario]' ";
		
	} else if ($_GET['tipo'] == 'divplacacarroreceita' || $_GET['tipo'] == 'divplacareboquereceita'  || $_GET['tipo'] == 'divplacasemireboquereceita' || $_GET['tipo'] == 'divplacaterceiroreboquereceita') {
	
		$sql .= "set ckreceita = '$_GET[situacao]' ,
				receitadata = '".date('d/m/Y')."',
				receitausuario = '$_SESSION[usuario]' ";
		
	} else if ($_GET['tipo'] == 'divplacacarroexperiencia' || $_GET['tipo'] == 'divplacareboqueexperiencia'  || $_GET['tipo'] == 'divplacasemireboqueexperiencia' || $_GET['tipo'] == 'divplacaterceiroreboqueexperiencia') {
	
		$sql .= "set experienciack = '$_GET[situacao]' ,
				experienciadata = '".date('d/m/Y')."',
				experienciausuario = '$_SESSION[usuario]' ";
	}	
	
	$sql .= "where placa = '$_GET[placa]'  ";
	
	$res = pg_exec($sql);

	if ($res) {
	  echo "<img src='../0bmp/t.png' align='absmiddle' border='0' width='10' height='10'> Salvo com sucesso ! ";
	} else {
	  echo "Nao foi possivel efetuar gravacao ! <br> codigo do erro: $res ";
	}
	
	

	//******************
	// gravo estatistica de operador
	//******************
	
	$selectclique = "select 
						to_char((current_timestamp - data), 'HH24:MI:SS') as temposemclique
					from tloginclique 
					where login = '$_SESSION[usuario]' 						
					order by id desc
					limit 1 					";
						
	$resstprot = pg_exec($selectclique);
	$temposemclique = pg_result($resstprot,'temposemclique');
		
	if ($temposemclique == '')
		$temposemclique = '00:00:00';	
		
	$sqlins = " insert into tloginclique (  login,data,tempo,placa)
		values('$_SESSION[usuario]','".date('d/m/Y H:i:s')."','$temposemclique','$_GET[placa]')  ";

	$res = pg_exec($sqlins);




//****************************************
//* SALVA OBSERVACOES  EM TVALIDA placa
//***************************************
// salva observacoes da pesquisa do paca
} else if ( $_GET['sq']	== 'gravavalidaplacaobs' ) {

	// tipo =  informa qual div vai aparecer a figura do positivo ou negativo
	// situacao =  t ou f, se ta liberado ou nao
	// codipessoa = o motorista
	// obs = observacao
	
	$_GET['obs'] = RemoveAcentos(strtoupper($_GET['obs']));
	
	if ($_GET['obs'] != '') {
		
		$sql = "
			update tvalidaplaca
			set obs  = ('(".date('dmy')." $_SESSION[usuario]) $_GET[obs];' || obs)
			where placa = '$_GET[placa]'  ";
					
		$res = pg_exec($sql);
		
		
		if ($_GET['protocolo'] <> '') {
		
		
			$stprot = "select statusprotocolo from tchamada where protocolo = '$_GET[protocolo]'  "; 
			$resstprot = pg_exec($stprot);
			$stprot = (int)pg_result($resstprot,'statusprotocolo'); 
			
			if ($stprot < 10) {
			    $stprot = ($stprot+1);
			}else  { 
				$stprot = 10;
			}
		
			$sqlchamada = "
				update tchamada
				set statusprotocolo = $stprot,
					resposta = ('* $_GET[obs];' || resposta)
				where protocolo = '$_GET[protocolo]'  ";
					
			$res = pg_exec($sqlchamada);
		}
		
		if ($res) {
		  echo "<img src='../0bmp/t.png' align='absmiddle' border='0' width='10' height='10'> Salvo com sucesso ! ";
		} else {
		  echo "Nao foi possivel efetuar gravacao ! <br> codigo do erro: $res ";
		}
	}
	
//****************************************
//* grava em tcontroleconsultoria
//***************************************
// essa tabela serve para controlar quantos registros foram enviados para a
// consultoria no mes, e envia email para facilicar a pesquisa
} else if ( $_GET['sq']	== 'gravacontroleconsultoria' ) {
	
	$sql = "
		insert into tcontroleconsultoria (codipessoa,competenciames,competenciaano,data,usuario,consultoriavia)
		values ('$_GET[codipessoa]','".date('m')."','".date('Y')."','".date('d/m/Y H:i:s')."','$_SESSION[usuario]','$_GET[consultoriavia]' )";
				
    $res = pg_exec($sql);

	echo '';
	
//********************************************************************
// MOSTRA RELATORIO DE CONSULTADOS E PERMITE REENCAMINHAR A RESPOSTA *
//********************************************************************	
} else if ( $_GET['sq']	== 'relatoriopesquisados' ) {

	$a = '';
	$sql = '';	

	if ( $_GET['filtro'] == 'motoristaeveiculo') {
	
		$sql = "				
			select 
				tchamada.senha,
				tchamada.placacarro,
				tchamada.placareboque,
				tchamada.placasemireboque,
				tchamada.placaterceiroreboque,
				tconta.nomeconta,
				tpessoa.nomepessoa,
				tchamada.usuario,
				tchamada.codipessoa,
				tchamada.validade,
				tchamada.statusprotocolo,		
				tchamada.liberado,	
				tchamada.conta,
				tchamada.pescon,
				tchamada.contaprincipal,
				to_char((tchamada.datasaida - tchamada.dataentrada), 'HH24:MI') as tempodemora,	
				tpessoa.cpfcnpj,
				to_char(dataentrada, 'DD/MM/YY') as dataentrada	
			from
				tconta,
				tchamada LEFT OUTER JOIN tpessoa ON (tchamada.codipessoa = tpessoa.codipessoa)
			where tchamada.conta = tconta.conta and
				tchamada.pescon != 'CAN'		";
	} else {
	
		$sql = "				
			select 
				tchamada.senha,
				tchamada.placacarro,
				tchamada.placareboque,
				tchamada.placasemireboque,
				tchamada.placaterceiroreboque,
				tconta.nomeconta,			
				tchamada.usuario,
				tchamada.pescon,
				tchamada.validade,
				tchamada.statusprotocolo,		
				tchamada.liberado,	
				to_char((tchamada.datasaida - tchamada.dataentrada), 'HH24:MI') as tempodemora,	
				tchamada.conta,
				tchamada.contaprincipal,				
				to_char(dataentrada, 'DD/MM/YY') as dataentrada	
			from
				tconta,
				tchamada
			where tchamada.conta = tconta.conta and
				tchamada.pescon != 'CAN' and
       			(( tchamada.placacarro <> '') or ( tchamada.placareboque <> '') or ( tchamada.placasemireboque <> '') or ( tchamada.placaterceiroreboque <> ''))	";
	
	}	

	// se o usuario e century entao da para consultar
	// todas as filiais, se nao for century so permite abrir
	// os cadastros da filial
	// 48813 � codigo da century
	
	// aqui eu verifico se o usuario nao e century,
	// para que o cliente nao veja todos os registros em pesquisa
	if ($_SESSION['contaprincipal'] != '48813') {
	
		//SE A CONTA FO ADMNISTRADOR DEIXA VISUALIZAR TODAS AS FILIAIS
		if ($_SESSION['nivelativo'] == '29') {

			$sql .= " and tchamada.contaprincipal = $_SESSION[contaprincipal]	";
		
			if ($_GET['grupo'] != '') {
			
				$sql .= " and tconta.grupo = '$_GET[grupo]'	";		
				
			}else{
			
				$sql .= " and tchamada.conta = $_GET[conta]	";
			}
		} else {
	
			// verifico se nao precisa mostrar todas as liberacoes do grupo
			// isso foi feito pq algumas filiais precisavam verificar a liberacao de outras
			// filiais, mas nao de todas timpo servis, entao na tabela tconta tem um campo
			// chamado grupo, se o grupo for igual ao session(grupo) login puxa todas as liberacoes
			// daquele grupo.
			
			if ($_GET['grupo'] != '') {
			
				$sql .= " and tconta.grupo = '$_GET[grupo]'	";		
				
			}else{
			
				$sql .= " and tchamada.conta = $_GET[conta]	";
			}	
		
		}	
	}else{
	
			if ($_GET['grupo'] != '') {
			
				$sql .= " and tconta.grupo = '$_GET[grupo]'	";		
				
			}else{
			
				$sql .= " and tchamada.conta = $_GET[conta]	";
			}	
	}

	if ($_GET['criterio'] == 'data') {
		$sql .= " and (   (  dataentrada >= cast('$_GET[datainicio] 00:00:00' as timestamp)  )  and (dataentrada <= cast('$_GET[datafim] 23:59:59' as timestamp) ) ) ";
	}else if ($_GET['criterio'] == 'dia') {
		$sql .= "	and datasaida >= CURRENT_DATE ";
	}	

	if ($_GET['criterio'] == 'pacote') {
		$sql .= " and pacote = '$_GET[pacote]'";
	}
	
	if ($_GET['pescon'] != '') {
		$sql .= " and pescon = '$_GET[pescon]'";
	}
	
	
//	$sql .= "	order by $_GET[ordenarpor] desc ";
	$sql .= "	order by protocolo desc ";

	
	if ($_GET['criterio'] == '10') {
		$sql .= "  limit 10 ";
	}else {
		$sql .= " limit 6000 ";
	}	
		
	$res = pg_exec($sql);

	// esta parte monta o relatorio
	
	if ( pg_numrows($res) > 0 ){
  
		if ( $_GET['filtro'] == 'motoristaeveiculo') { 
  
  
			$a = "<table class='table table-striped table-bordered table-condensed table-hover'>";
			$a .= "<thead>";
			$a .= "<tr><td > Data</td><td > P/C</td><td > Conta </td><td > Sit </td><td > Senha </td><td > Pesquisa </td><td >Validade *Pesquisa*</td><td > Resposta </td><td>Demora</td><td>Usr</td></tr>";
			$a .= "</thead>";
			$a .= "<tbody tbody-striped>";  
		
			for ($i=0; $i < pg_numrows($res ); $i++) {
			
				$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
				if ( $arr['liberado'] == 't') {
					$a .= "<tr ><td> $arr[dataentrada] </td><td >$arr[pescon]</td><td > $arr[nomeconta]</td><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'></td><td>$arr[senha] </td><td> $arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] $arr[placaterceiroreboque] </td><td>$arr[validade]</td> <td class='botonoff'><a href='#' onclick=\"mostraresposta('$arr[senha]');\" ><div id='buttonaz'><img src='../0bmp/pasta.png' align='absmiddle' border='0' width='25' height='25'> ver</div></a></td>";
				}else{
					$a .= "<tr><td> $arr[dataentrada] </td><td >$arr[pescon]</td><td > $arr[nomeconta]</td><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'></td><td align=center> --- </td><td> $arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] $arr[placaterceiroreboque]</td><td align=center> --- </td> <td class='botonoff'><a href='#' onclick=\"mostraresposta('$arr[senha]');\" ><div id='buttonaz'><img src='../0bmp/pasta.png' align='absmiddle' border='0' width='25' height='25'> Ver </div></a></td>";
				}	
				
				$a .= " <td> $arr[tempodemora]</td><td> $arr[usuario]</td></tr>";
				// funcao uploaddocumentos() est� na pasta /funcoes/telauploadmotoristaveiculo.js e incluido em /0interrisco/registrospendentes.php

				
			}
			$a .= "<tr class='letra_gris'><td colspan=8> $i Registro(s) *Obs.: Independente da validade da pesquisa, para motoristas autonomos necessita uma consulta a cada carregamento ! </td></tr>";
			$a .= "<tr class='letra_gris'><td colspan=8> Limite maximo 2000 registros </td></tr>";
			$a .= "</table>";
			
		} else {

			$a = "<table class='tabla_listado'>";
			$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Data </td><td class=menuiz_botonoff> Conta </td><td class=menuiz_botonoff> Sit </td><td class=menuiz_botonoff> Senha </td><td class=menuiz_botonoff> Veiculo </td><td class=menuiz_botonoff> Reboque </td><td class=menuiz_botonoff> SemiReboque </td><td class=menuiz_botonoff> terceiroReboque </td><td class=menuiz_botonoff> Val. Pesquisa* </td><td class=menuiz_botonoff> Resposta </td><td>Tempo Demora</td></tr>";
			
			for ($i=0; $i < pg_numrows($res ); $i++) {
			
				$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
				if ( $arr['liberado'] == 't') {
					$a .= "<tr class='fila_paginacion'><td> $arr[dataentrada]</td><td > $arr[nomeconta]</td><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'></td><td>$arr[senha] </td><td>  $arr[placacarro] </td><td> $arr[placareboque] </td><td> $arr[placasemireboque] </td><td> $arr[placaterceiroreboque] </td><td>$arr[validade]</td> <td class='botonoff'><a href='#' onclick=\"mostraresposta('$arr[senha]');\" ><div id='buttonaz'><img src='../0bmp/pasta.png' align='absmiddle' border='0' width='25' height='25'> Ver</div></a></td>";
				}else{
					$a .= "<tr class='fila_subtotal'><td> $arr[dataentrada]</td><td > $arr[nomeconta]</td><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'></td><td align=center> --- </td><td> $arr[placacarro] </td><td> $arr[placareboque] </td><td> $arr[placasemireboque] </td><td> $arr[placaterceiroreboque] </td><td align=center> --- </td> <td class='botonoff'><a href='#' onclick='\"mostraresposta('$arr[senha]');\" ><div id='buttonaz'><img src='../0bmp/pasta.png' align='absmiddle' border='0' width='25' height='25'> Ver</div></a></td>";
				}	
				
				$a .= "<td class='botonoff'> <a href='#' onclick=uploaddocumentos('$arr[cpfcnpj]','$arr[codipessoa]','$arr[placacarro]','$arr[placareboque]','$arr[placasemireboque]','$arr[placaterceiroreboque]')><img src='../0bmp/upload.png' width='40' height='40'  > </a> </a> </td><td> $arr[tempodemora]</td></tr>";
				// funcao uploaddocumentos() est� na pasta /funcoes/telauploadmotoristaveiculo.js e incluido em /0interrisco/registrospendentes.php

				
				
			}
			$a .= "<tr class='letra_gris'><td colspan=7> $i Registro(s) *Obs.: Independente da validade da pesquisa, para motoristas autonomos necessita uma consulta a cada carregamento ! </td></tr>";
			$a .= "<tr class='letra_gris'><td colspan=7> Limite maximo 6000 registros </td></tr>";
			$a .= "</table>";
		
		}	
		
	}else{
	
		$a = faviso2(",","Nao consta registro para o criterio de busca selecionado ");
	
	}
	
	
	echo " $a ";//<br><br> sql = $sql <br><br>datafim $_GET[datafim] <br><br>  data inicio $_GET[datainicio] <br><br> conta principal $_GET[contaprincipal] <br><br> conta $_GET[conta] <br><br> criterio $_GET[criterio]";
//	echo " $a $sql";//<br><br> sql = $sql <br><br>datafim $_GET[datafim] <br><br>  data inicio $_GET[datainicio] <br><br> conta principal $_GET[contaprincipal] <br><br> conta $_GET[conta] <br><br> criterio $_GET[criterio]";


//********************************************************************
// MOSTRA RELATORIO DE CONSULTADOS E PERMITE REENCAMINHAR A RESPOSTA *
//********************************************************************	
} else if ( $_GET['sq']	== 'relatoriocadastradostotal' ) {

	$a = '';
	$sql = '';	

	if ( $_GET['filtro'] == 'motoristaeveiculo') {
	
		$sql = "				
			select 
				tchamada.senha,
				tchamada.placacarro,
				tchamada.placareboque,
				tchamada.placasemireboque,
				tchamada.placaterceiroreboque,
				tconta.nomeconta,
				tpessoa.nomepessoa,
				tchamada.usuario,
				tchamada.validade,
				tchamada.statusprotocolo,		
				tchamada.liberado,	
				tchamada.conta,
				tchamada.contaprincipal,
				tpessoa.cpfcnpj,
				to_char(dataentrada, 'DD/MM/YY') as dataentrada	
				
			from
				tconta,
				tchamada LEFT OUTER JOIN tpessoa ON (tchamada.codipessoa = tpessoa.codipessoa)
			where tchamada.conta = tconta.conta and
				tchamada.pescon != 'CAN'		";
								
	} else {
	
		$sql = "				
			select 
				tchamada.senha,
				tchamada.placacarro,
				tchamada.placareboque,
				tchamada.placasemireboque,
				tchamada.placaterceiroreboque,
				tconta.nomeconta,			
				tchamada.usuario,
				tchamada.validade,
				tchamada.statusprotocolo,		
				tchamada.liberado,	
				tchamada.conta,
				tchamada.contaprincipal,				
				to_char(dataentrada, 'DD/MM/YY') as dataentrada	
			from
				tconta,
				tchamada
			where tchamada.conta = tconta.conta and
				tchamada.pescon != 'CAN' and
       			(( tchamada.placacarro <> '') or ( tchamada.placareboque <> '') or ( tchamada.placasemireboque <> '') or ( tchamada.placaterceiroreboque <> ''))	";
	
	}	

	// se o usuario e century entao da para consultar
	// todas as filiais, se nao for century so permite abrir
	// os cadastros da filial
	// 48813 � codigo da century
	
	if ( $_SESSION['contaprincipal'] == '48813') {
	
		$sql .= " and tchamada.contaprincipal = $_GET[contaprincipal]	";
		if ($_GET['conta'] != '') {
			$sql .= " and tchamada.conta = $_GET[conta]	";			
		}
		
	}else{
	
		$sql .= " and tchamada.contaprincipal = $_GET[contaprincipal]	";
		
		// se mao tiver grupo entao pega somente a filial
		if ( trim($_SESSION['grupo']) == '') {
		
			$sql .= " and tchamada.conta = $_GET[conta]	";
			
		} else {
		   
		   $sql .= " and tconta.grupo = '$_SESSION[grupo]'	";
			
		   // se grupo foi cadastrado entao pega as liberacoes de todas as filiais
		   // isso foi feito para gerenciadora verificar 
		}
				
	}	

	if ($_GET['criterio'] == 'data') {
		$sql .= " and (   (  dataentrada >= cast('$_GET[datainicio]' as date)  )  and (dataentrada <= cast('$_GET[datafim]' as date) ) ) ";
	}else if ($_GET['criterio'] == 'dia') {
		$sql .= "	and datasaida >= CURRENT_DATE ";
	}	

	$sql .= "	order by $_GET[ordenarpor] desc ";

	if ($_GET['criterio'] == '10') {
		$sql .= "  limit 10 ";
	}else {
		$sql .= " limit 2000 ";
	}	
		
	$res = pg_exec($sql);

	// esta parte monta o relatorio
	
	if ( pg_numrows($res) > 0 ){
  
		if ( $_GET['filtro'] == 'motoristaeveiculo') {
  
			$a = "<table class='tabla_listado'>";
			$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Data </td><td class=menuiz_botonoff> Conta </td><td class=menuiz_botonoff> Sit </td><td class=menuiz_botonoff> Senha </td><td class=menuiz_botonoff> Pesquisa </td><td class=menuiz_botonoff> Val. Pesquisa* </td><td class=menuiz_botonoff> Resposta </td></tr>";
			
			for ($i=0; $i < pg_numrows($res ); $i++) {
			
				$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
				if ( $arr['liberado'] == 't') {
					$a .= "<tr class='fila_paginacion'><td> $arr[dataentrada]</td><td > $arr[nomeconta]</td><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'></td><td>$arr[senha] </td><td> $arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] $arr[placaterceiroreboque] </td><td>$arr[validade]</td> <td class='botonoff'><a href='#' onclick=\"mostraresposta('$arr[senha]');\" ><div id='buttonaz'><img src='../0bmp/pasta.png' align='absmiddle' border='0' width='25' height='25'> Visualizar</div></a></td></tr>";
				}else{
					$a .= "<tr class='fila_subtotal'><td> $arr[dataentrada]</td><td > $arr[nomeconta]</td><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'></td><td align=center> --- </td><td> $arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] $arr[placaterceiroreboque] </td><td align=center> --- </td> <td class='botonoff'><a href='#' onclick=\"mostraresposta('$arr[senha]');\" ><div id='buttonaz'><img src='../0bmp/pasta.png' align='absmiddle' border='0' width='25' height='25'> Visualizar</div></a></td></tr>";
				}	
			}
			$a .= "<tr class='letra_gris'><td colspan=7> $i Registro(s) *Obs.: Independente da validade da pesquisa, para motoristas autonomos necessita uma consulta a cada carregamento ! </td></tr>";
			$a .= "<tr class='letra_gris'><td colspan=7> Limite maximo 2000 registros </td></tr>";
			$a .= "</table>";
			
		} else {

			$a = "<table class='tabla_listado'>";
			$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Data </td><td class=menuiz_botonoff> Conta </td><td class=menuiz_botonoff> Sit </td><td class=menuiz_botonoff> Senha </td><td class=menuiz_botonoff> Veiculo </td><td class=menuiz_botonoff> Reboque </td><td class=menuiz_botonoff> SemiReboque </td><td class=menuiz_botonoff> terceiroReboque </td><td class=menuiz_botonoff> Val. Pesquisa* </td><td class=menuiz_botonoff> Resposta </td></tr>";
			
			for ($i=0; $i < pg_numrows($res ); $i++) {
			
				$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
				if ( $arr['liberado'] == 't') {
					$a .= "<tr class='fila_paginacion'><td> $arr[dataentrada]</td><td > $arr[nomeconta]</td><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'></td><td>$arr[senha] </td><td>  $arr[placacarro] </td><td> $arr[placareboque] </td><td> $arr[placasemireboque] </td><td> $arr[placaterceiroreboque] </td><td>$arr[validade]</td> <td class='botonoff'><a href='#' onclick=\"mostraresposta('$arr[senha]');\" ><div id='buttonaz'><img src='../0bmp/pasta.png' align='absmiddle' border='0' width='25' height='25'> Visualizar</div></a></td></tr>";
				}else{
					$a .= "<tr class='fila_subtotal'><td> $arr[dataentrada]</td><td > $arr[nomeconta]</td><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'></td><td align=center> --- </td><td> $arr[placacarro] </td><td> $arr[placareboque] </td><td> $arr[placasemireboque] </td><td> $arr[placaterceiroreboque] </td><td align=center> --- </td> <td class='botonoff'><a href='#' onclick=\"mostraresposta('$arr[senha]');\" ><div id='buttonaz'><img src='../0bmp/pasta.png' align='absmiddle' border='0' width='25' height='25'> Visualizar</div></a></td></tr>";
				}	
			}
			$a .= "<tr class='letra_gris'><td colspan=7> $i Registro(s) *Obs.: Independente da validade da pesquisa, para motoristas autonomos necessita uma consulta a cada carregamento ! </td></tr>";
			$a .= "<tr class='letra_gris'><td colspan=7> Limite maximo 2000 registros </td></tr>";
			$a .= "</table>";
		
		}	
		
	}else{
	
		$a = faviso2(",","Nao consta registro para o criterio de busca selecionado ");
	
	}
	
	
	echo " $a ";//<br><br> sql = $sql <br><br>datafim $_GET[datafim] <br><br>  data inicio $_GET[datainicio] <br><br> conta principal $_GET[contaprincipal] <br><br> conta $_GET[conta] <br><br> criterio $_GET[criterio]";
//	echo " $a $sql";//<br><br> sql = $sql <br><br>datafim $_GET[datafim] <br><br>  data inicio $_GET[datainicio] <br><br> conta principal $_GET[contaprincipal] <br><br> conta $_GET[conta] <br><br> criterio $_GET[criterio]";


//********************************************************************
// MOSTRA RELATORIO DE CONSULTADOS E PERMITE REENCAMINHAR A RESPOSTA *
//********************************************************************	
} else if ( $_GET['sq']	== 'relatorioconsultados' ) {
	
	$a = '';
	$sql = '';	
	$statusvalidade = '';
    $contador = 0;
    $somatempo = '';
	//$_GET[contaprincipal] = '';
		
	$horas = Array();
	
	if ( true)	{	
		
		// esta parte monta o relatorio
			
		$sql = "				
			select 
				tchamada.senha,
				tchamada.placacarro,
				tchamada.placareboque,
				tchamada.placasemireboque,
				tchamada.placaterceiroreboque,
				tconta.nomeconta,
				tpessoa.nomepessoa,
				tchamada.usuario,
				tpessoa.cpfcnpj,
				tchamada.validade,
				tchamada.statusprotocolo,		
				tchamada.liberado,	
				tchamada.conta,
				tchamada.contaprincipal,			
				tchamada.tipovinculo,
				tchamada.resposta,
				tchamada.pescon,
				tchamada.pacote,
				tchamada.codipessoa,                      
                to_char((datasaida - dataentrada), 'HH24:MI') as tempodemora,	
				to_char(dataentrada, 'DD/MM/YY') as dataentrada,
				to_char((tchamada.validade - current_timestamp), 'DD') as tempovalidade			
			from
				tconta,
				 tchamada LEFT OUTER JOIN tpessoa ON (tchamada.codipessoa = tpessoa.codipessoa) 
			where tchamada.conta = tconta.conta	
				
				  ";
	
		if ( $_GET['pescon'] == 'PES' ) {
			$sql .= " and pescon = 'PES' ";		
		}else if ( $_GET['pescon'] == 'CON') {
			$sql .= " and pescon = 'CON' ";		
		}else if ( $_GET['pescon'] == 'REN') {
			$sql .= " and pescon = 'REN'   ";
		}else if ( $_GET['pescon'] == 'CAN') {
			$sql .= " and pescon = 'CAN'   ";
		}

		if ( $_GET['liberado'] == 't') {
			$sql .= " and  TCHAMADA.LIBERADO = 't' ";		
		}else if ( $_GET['liberado'] == 'f') {

			$sql .= " and 	tchamada.codipessoa not in (SELECT 	CODIPESSOA FROM TCHAMADA
														WHERE 	CODIPESSOA = TCHAMADA.CODIPESSOA AND
																CONTA = TCHAMADA.CONTA AND
																LIBERADO = 't')";
		}
	
//<tr><td> Filtro conta:  </td><td ><select id='todogrupo' class='redonda' tabindex=1 >
//<option value='veconta'>Busca somente uma conta
//<option value='vegrupo'>Busca contas do Grupo
//<option value='vegrupoprincipal'>Busca todos do Grupo Principal
//<option value='vecontaprincipal'>Busca todos da conta Principal
//<option value='vecontasprincipais'>Todos das contas prinicipais</select>  </td>
				
		//se operador >= 16 ve grupo
		
		//se operador >= 17 ve grupo principal
		
		//se operador >= 29 ve conta principal
		
		
		if ($_GET['conta'] == '' ) {
			$_GET['conta'] =  '-1';
			$grupo = '-1'; 
			$grupoprincipal = '-1';
		
		}	
		
		//se operador >= 30 ve todas as contas principais
		$sqlgrupo = "select grupo,grupoprincipal from tconta where conta = '$_GET[conta]' "; 
		$resgrupo = pg_exec($sqlgrupo);
		
		if ( pg_numrows($resgrupo) > 0 ){
			$grupo = pg_result($resgrupo,'grupo'); 
			$grupoprincipal = pg_result($resgrupo,'grupoprincipal'); 
		}
		
		if ( $_SESSION['nivelativo'] >= 30  && $_SESSION['contaprincipal'] == '48813' ) {

			//nao faco nada pq vejo todas as contas principais
			$log .= '<br>1';		
			
			
						
			if ( $_GET['todogrupo'] == 'veconta' ) {
			
				$log .= '<br>1a';	
				$sql .= " and tchamada.conta = '$_GET[conta]'	";	
							
			}else if ( $_GET['todogrupo'] == 'vegrupo') {			
				
				$log .= '<br>1b';	
				$sql .= " and tconta.grupo = '$grupo'	";
							
			}else if ( $_GET['todogrupo'] == 'vegrupoprincipal') {
				
				$log .= '<br>1c';				
				$sql .= " and tconta.grupoprincipal = '$grupoprincipal'	";
				
			}else if ( $_GET['todogrupo'] == 'vecontaprincipal') {
				
				$log .= '<br>1d';	
				$sql .= " and tchamada.contaprincipal = $_GET[contaprincipal] ";
				
			}else if ( $_GET['todogrupo'] == 'vecontasprincipais') {
				
				$log .= '<br>1e';	
				// nao faco nada pq ja puxa sem filtro
			}
			
		} else if ( $_SESSION['nivelativo'] >= 29 && $_SESSION['contaprincipal'] != '' ) {

			//vecontasprincipais, ignora puxa s� a conta
			//ignoro veconta pq � o nivel basico

			if ( $_GET['todogrupo'] == 'vegrupo' && $_SESSION['grupo'] != '') {			
				
				$log .= '<br>1b';	
				$sql .= " and tconta.grupo = '$grupo'	";
							
			}else if ( $_GET['todogrupo'] == 'vegrupoprincipal' && $_SESSION['grupoprincipal'] != '') {
				
				$log .= '<br>1c';				
				$sql .= " and tconta.grupoprincipal = '$grupoprincipal'	";
				
			}else if ( $_GET['todogrupo'] == 'vecontaprincipal') {
				
				$log .= '<br>1d';	
				$sql .= " and tchamada.contaprincipal = '$_GET[contaprincipal]' ";
							
			} else {
				
				$sql .= " and tchamada.conta = '$_GET[conta]'	";	
				
			}	
			
			$log .= '<br>2';	
		
		} else if ( $_SESSION['nivelativo'] >= 17  && $_SESSION['grupoprincipal'] != '' || $_SESSION['ativogrupoprincipal'] == 't' ) {
			
			if ( $_GET['todogrupo'] == 'vegrupo' && $_SESSION['grupo'] != '') {			
				
				$log .= '<br>1b';	
				$sql .= " and tconta.grupo = '$_SESSION[grupo]'	";
							
			}else if ( $_GET['todogrupo'] == 'vegrupoprincipal' && $_SESSION['grupoprincipal'] != '') {
				
				$log .= '<br>1c';				
				$sql .= " and tconta.grupoprincipal = '$_SESSION[grupoprincipal]'	";
				
			} else {
				
				$sql .= " and tchamada.conta = '$_SESSION[conta]'	";	
				
			}

			
		} else if ( $_SESSION['nivelativo'] >= 16  && $_SESSION['grupo'] != '' ) {	
		
			if ( $_GET['todogrupo'] == 'vegrupo' && $_SESSION['grupo'] != '') {			
				
				$log .= '<br>1b';	
				$sql .= " and tconta.grupo = '$_SESSION[grupo]'	";
							
			}else {
				
				$sql .= " and tchamada.conta = '$_SESSION[conta]'	";	
				
			}		
			$log .= '<br>4';	
			
		} else {
				
			$sql .= " and tchamada.conta = '$_GET[conta]'	";
			$log .= '<br>5';
		}				
			
		$sql .= " and (   (  dataentrada >= cast('$_GET[datainicio] 00:00:00' as timestamp)  )  and (dataentrada <= cast('$_GET[datafim] 23:59:59' as timestamp) ) ) ";

		// classificacao
		$sql .= "	order by $_GET[criterio] desc  limit 2000";

//$f = fopen('log_relatoriopesquisadosporcpfnome.txt', 'w');
//fwrite($f,"\n sql: \n $sql 
//	\n nivelativo $_SESSION[nivelativo]
//	\n todogrupo $_GET[todogrupo] 
//	\n conta principal $_SESSION[contaprincipal] 
//	\n grupo $_SESSION[grupo] 
//	\n grupoprincipal $_SESSION[grupoprincipal] 
//	\n get conta $_GET[conta] 
//	\n ativogrupoprincipal = $_SESSION[ativogrupoprincipal] 
//	\n log $log " );		
//fclose($f);
			
		$res = pg_exec($sql);

		// esta parte monta o relatorio
		
		if ( pg_numrows($res) > 0 ){
	  
			$a = "<table>";
			$a .= "<tr><td><h3>$_SESSION[login]</td>";
			$a .= "<tr><td> </td>";
			$a .= "<tr><td><h3>Relatorio De Pesquisas </td>";
			$a .= "<tr><td> </td>";
			$a .= "<tr><td>Classificacao: $_GET[criterio] </td>";
			$a .= "<tr><td>Filtro: Data Inicio $_GET[datainicio] Data fim $_GET[datafim] </td>";			
			$a .= "<tr><td>Data emissao relatorio: ".date('d/m/Y H:i')."</td>";
			$a .= "</table><br>";

	  
			$a .= "<table class='tabla_listado'>";
			
			
			//if ( $arr['liberado'] == 't') {

				$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Data </td><td class=menuiz_botonoff> Conta </td><td class=menuiz_botonoff>Situacao  Senha </td><td class=menuiz_botonoff> Cpf Nome Placa</td><td class=menuiz_botonoff> Validade Pesquisa </td><td class=menuiz_botonoff> Status Pesquisa</td><td class=menuiz_botonoff> Resp</td><td class=menuiz_botonoff>Integ<br>racao </td><td class=menuiz_botonoff>Time</td></tr>";
			//}else{
			//	$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Data </td><td class=menuiz_botonoff> Conta </td><td class=menuiz_botonoff>Ult <br>Sit </td><td class=menuiz_botonoff> Vinculo </td><td class=menuiz_botonoff> Pesquisado </td><td class=menuiz_botonoff colspan=2> Detalhes </td><td class=menuiz_botonoff> Resposta </td></tr>";
			
			//}
			
			for ($i=0; $i < pg_numrows($res ); $i++) {
			
				$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
				
				if ( $arr['tempovalidade'] < 0 ) {
				
					$statusvalidade = "<font color='red'>Vencido  $arr[tempovalidade] dias <font>";
					
				}else {

					$statusvalidade = "Vigente $arr[tempovalidade] dias";
					
				}			
				
				if ( $arr['liberado'] == 't') {
				
					// verifica se � autonomo, nao deve colocar validade da ficha 
					if ($arr['tipovinculo'] == 'AUTONOMO') { 
						$a .= "<tr class='fila_subtotal'><td> $arr[dataentrada] $arr[tipovinculo] </td><td > $arr[nomeconta] </td><td>Liberado $arr[senha] </td> <td>  $arr[cpfcnpj] <b>$arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] $arr[placaterceiroreboque] </td><td> --- </td> <td> --- </td> <td align=center><a href='#' onclick=\"mostraresposta('$arr[senha]');\"  > Ver</a></td>";
						//$a .= "<tr class='fila_paginacion'><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
						
							$a .= "<td> $arr[pacote]</td>";
						
						
					}else{
					
						// verifica o tempo de validade
						$a .= "<tr class='fila_subtotal'><td> $arr[dataentrada] $arr[tipovinculo] </td><td > $arr[nomeconta] </td><td>Liberado $arr[senha] </td> <td> $arr[cpfcnpj] <b>$arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] $arr[placaterceiroreboque]</td><td>Validade: $arr[pescon]<br>$arr[validade]  </td> <td align=center>$statusvalidade </td> <td align=center><a href='#' onclick=\"mostraresposta('$arr[senha]');\"  > Ver </a></td>";
						//$a .= "<tr class='fila_paginacion'><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
						
							$a .= "<td> $arr[pacote]</td>";

					}				
					
				}else{
					$a .= "<tr class='fila_subtotal'><td> $arr[dataentrada] $arr[tipovinculo]  </td><td > $arr[nomeconta]  </td><td>Pendente <br> --- </td> <td> $arr[cpfcnpj] <b>$arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] $arr[placaterceiroreboque]</td><td colspan=2 class='redonda' > $arr[resposta] </td> <td></td><td align=center><a href='#' onclick=\"mostraresposta('$arr[senha]');\" > Ver </a></td>";
					//$a .= "<tr class='fila_paginacion'><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>";
					
							$a .= "<td> $arr[pacote]</td>";

					
				}

                if ($arr['pescon'] <> 'CAN') {
                    					
                    $tempodemora    = $arr['tempodemora'];
                    					
					//nao conta os zerados
					if ($tempodemora != '00:00') {
					
						//nao conta mais de 4 horas	
						if ( (int)substr($tempodemora,0,2) <= 4 ) { 		 
							$contador++;  
							$hora[$contador] = strtotime($arr['tempodemora'].":00");    
						}	

					}	
						                                        
                }   else {
                    $tempodemora    = '00:00';
                }            

				// esse negativar ta declarado em relatorioconsultados.php
			    $a .="<a name='ancora_neg' id='ancora_neg'></a>";				
				$a .="<td>$tempodemora  </td></tr>";
	//			$a .="<td>$tempodemora  </td><td><a href='#ancora_neg' onclick=negativar('$arr[nomepessoa]','$arr[codipessoa]','$arr[contaprincipal]','$arr[conta]','$arr[placacarro]','$arr[placareboque]','$arr[placasemireboque]');><div id='buttonaz'><img src='../0bmp/neg.png' align='absmiddle' border='0' width='15' height='15'>negativar</div></a></td></tr>"; 
					
			}
             
            //$somatempo1 =  ($somatempo /$contador) ;        
            
			$a .= "<tr><td colspan=6>$i Registro(s)</td ><td colspan=5>  Media Demora.:".date("H:i:s", array_sum($hora)/count($hora))."</td><td ></td>";
			$a .= "</table> ";
			
		}else{
			$a = faviso2(",","Nao consta registro para o criterio de busca selecionado ");
		}
	
	}
	
//echo "<br> $sql";	
	
	//*************************
	//*************************
	// pego os registros de rdo
	//*************************
	//************************* 

	$sqlserasa ="
		select 						
			to_char(dataentrada, 'DD/MM/YY HH24:MI') as dataentrada,
			to_char(datasaida, 'DD/MM/YY HH24:MI') as datasaida,
			to_char((datasaida - dataentrada), 'HH24:MI') as tempo,			
			tconta.nomeconta,			
			tconta.fone as foneconta,
			tcontaprincipal.razaosocial,
			tserasa.cpfcnpj,
			tserasa.statusprotocolo,			
			tserasa.usuarioqueinseriu,
			tserasa.protocolo,
			tserasa.uf,
			tserasa.tipocpfcnpj,
			tserasa.historico,
			tserasa.resposta,
			tconta.email,
			tpessoa.codipessoa,
			tpessoa.nomepessoa,
			tpessoafisica.rg,
			tpessoa.copiadoc,
			tpessoa.copiadocurl,
			tpessoafisica.ufrg,
			tpessoafisica.nomemae
			
		from tserasa LEFT OUTER JOIN tpessoafisica ON (tserasa.codipessoa = tpessoafisica.codipessoa),			
			tconta,
			tpessoa,
			tcontaprincipal
		where tserasa.conta = tconta.conta and
			tconta.contaprincipal = tcontaprincipal.contaprincipal and
			tserasa.cpfcnpj = tpessoa.cpfcnpj  ";	
			
	//tserasa.statusprotocolo < 10 ";	
	// se nao for  contaprincipal century mostra s� a ficha
	
	if ( $_SESSION['nivelativo'] >= 30  && $_SESSION['contaprincipal'] == '48813' ) {

		//nao faco nada pq vejo todas as contas principais
		$log .= '<br>1';				
					
		if ( $_GET['todogrupo'] == 'veconta' ) {
		
			$log .= '<br>1a';	
			$sqlserasa .= " and tserasa.conta = '$_GET[conta]'	";	
						
		}else if ( $_GET['todogrupo'] == 'vegrupo') {			
			
			$log .= '<br>1b';	
			$sqlserasa .= " and tconta.grupo = '$grupo'	";
						
		}else if ( $_GET['todogrupo'] == 'vegrupoprincipal') {
			
			$log .= '<br>1c';				
			$sqlserasa .= " and tconta.grupoprincipal = '$grupoprincipal'	";
			
		}else if ( $_GET['todogrupo'] == 'vecontaprincipal') {
			
			$log .= '<br>1d';	
			$sqlserasa .= " and tserasa.contaprincipal = $_GET[contaprincipal] ";
			
		}else if ( $_GET['todogrupo'] == 'vecontasprincipais') {
			
			$log .= '<br>1e';	
			// nao faco nada pq ja puxa sem filtro
		}
		
	} else if ( $_SESSION['nivelativo'] >= 29 && $_SESSION['contaprincipal'] != '' ) {

		//vecontasprincipais, ignora puxa s� a conta
		//ignoro veconta pq � o nivel basico

		if ( $_GET['todogrupo'] == 'vegrupo' && $_SESSION['grupo'] != '') {			
			
			$log .= '<br>1b';	
			$sqlserasa .= " and tconta.grupo = '$grupo'	";
						
		}else if ( $_GET['todogrupo'] == 'vegrupoprincipal' && $_SESSION['grupoprincipal'] != '') {
			
			$log .= '<br>1c';				
			$sqlserasa .= " and tconta.grupoprincipal = '$grupoprincipal'	";
			
		}else if ( $_GET['todogrupo'] == 'vecontaprincipal') {
			
			$log .= '<br>1d';	
			$sqlserasa .= " and tserasa.contaprincipal = '$_GET[contaprincipal]' ";
						
		} else {
			
			$sqlserasa .= " and tserasa.conta = '$_GET[conta]'	";	
			
		}	
		
		$log .= '<br>2';	
	
	} else if ( $_SESSION['nivelativo'] >= 17  && $_SESSION['grupoprincipal'] != '' || $_SESSION['ativogrupoprincipal'] == 't' ) {
		
		if ( $_GET['todogrupo'] == 'vegrupo' && $_SESSION['grupo'] != '') {			
			
			$log .= '<br>1b';	
			$sqlserasa .= " and tconta.grupo = '$_SESSION[grupo]'	";
						
		}else if ( $_GET['todogrupo'] == 'vegrupoprincipal' && $_SESSION['grupoprincipal'] != '') {
			
			$log .= '<br>1c';				
			$sqlserasa .= " and tconta.grupoprincipal = '$_SESSION[grupoprincipal]'	";
			
		} else {
			
			$sqlserasa .= " and tserasa.conta = '$_SESSION[conta]'	";	
			
		}

		
	} else if ( $_SESSION['nivelativo'] >= 16  && $_SESSION['grupo'] != '' ) {	
	
		if ( $_GET['todogrupo'] == 'vegrupo' && $_SESSION['grupo'] != '') {			
			
			$log .= '<br>1b';	
			$sqlserasa .= " and tconta.grupo = '$_SESSION[grupo]'	";
						
		}else {
			
			$sqlserasa .= " and tserasa.conta = '$_SESSION[conta]'	";	
			
		}		
		$log .= '<br>4';	
		
	} else {
			
		$sqlserasa .= " and tserasa.conta = '$_GET[conta]'	";
		$log .= '<br>5';
	}
	
	$sqlserasa .= " and (   (  dataentrada >= cast('$_GET[datainicio] 00:00:00' as timestamp)  )  and (dataentrada <= cast('$_GET[datafim] 23:59:59' as timestamp) ) ) ";
	
	$res = pg_exec($sqlserasa);	
		
	if ( pg_numrows($res) > 0 ){
	
		
		//$serasa = "<table width=100>";
		//$serasa .= "	<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=9><img src='../0bmp/cal.png' width='25' height='25' border='0'  align='absmiddle'><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'>  (". pg_numrows($res).") Registros -  usuario ($_SESSION[conta]) class ($_SESSION[layoutazregistrospendentes]) Pesquisas RDO </td></tr>";
	

		$tl = "<table class='table table-striped table-bordered table-condensed table-hover'>";		$tl .= "<thead>";
		$tl .= "<tr><th scope='col' colspan=5>Registros em RDO (". pg_numrows($res).") usuario $_SESSION[usuario] </th></tr>";
		$tl .= "<tr><th scope='col'>Entrada</th><th scope='col'>Saida</th><th scope='col'>Conta</th><th scope='col'>Pesquisado</th><th scope='col'>Produto</th><th scope='col'>Tempo</th></tr>";
		$tl .= "</thead>";
		$tl .= "<tbody tbody-striped>";
	
		$contador = 0;
		$hora = Array();

 		for ($i=0; $i < pg_numrows($res ); $i++) {
			
			$arrserasa = pg_fetch_array($res,$i,PGSQL_ASSOC);
				
			// novo registro mostra a consulta civil
			$tl .= "<tr>
			<td>$arrserasa[dataentrada] <br>$arrserasa[usuario] </td>
			<td>$arrserasa[datasaida]</td>
			<td>$arrserasa[razaosocial] <b>$arrserasa[nomeconta]</b>  </td>
			<td> CPF: <b>$arrserasa[cpfcnpj] $arrserasa[uf] -  $arrserasa[nomepessoa] </b> ";

			if ($_SESSION['nivelativo'] >= 17) {

				if ( strlen ($arrserasa['copiadoc'] ) > 2 ) {
					
					//$tl .="<br>";			
						
					$pieces = explode(";", $arrserasa['copiadoc']);
					foreach($pieces as $arq){				
						if (trim($arq) != '')
							$tl .="&nbsp;&nbsp;<a href='../0uploaddoc/$arq' target='_blank'>$arq</a> ";
					}	
					
				}
				
				if ($_SESSION['contaprincipal'] == 48813) {
					if ( strlen ($arrserasa['copiadocurl'] ) > 2 ) {
						//$tl .="<br>";			
						$pieces = explode(";", $arrserasa['copiadocurl']);
						foreach($pieces as $arq){				
							if ($arq != '')
								$tl .="&nbsp;&nbsp;<a href='$arq' target='_blank'>".str_replace('http://www.noxgr.srv.br/noxwebcliente/Uploads/','',$arq )."</a> ";
						}				
					}
				}	
			}
			
			
					 
			$contador++;  
			
			
			
			// verifico se nao é um dia para outro 
			
			if ( substr($arrserasa['dataentrada'],0,2) == substr($arrserasa['datasaida'],0,2) )								
				$hora[$contador] = strtotime($arrserasa['tempo'].":00");    
				
			$tl .= "<td>$arrserasa[historico] </td>";

			
			$tl .= "<td>$arrserasa[tempo] </td></tr>";
			// funcao uploaddocumentos() est� na pasta /funcoes/telauploadmotoristaveiculo.js e incluido em /0interrisco/registrospendentes.php				
			//uploaddocumentos(document.getElementById('cpfcnpj').value,document.getElementById('codipessoa').value,'','');
			
		//}	
						
	
					
		}	

		$tl .= "<tr><td colspan=2>$i Registro(s)</td ><td></td> <td> Media demora.: </td> <td>".date("H:i:s", array_sum($hora)/count($hora))."</td>";

		
	}else{
		$serasa = "<table width=100%>";
		$serasa .= "	<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=9><img src='../0bmp/cal.png' width='25' height='25' border='0'  align='absmiddle'><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'>  (". pg_numrows($res).") Registros -  usuario ($_SESSION[conta]) class ($_SESSION[layoutazregistrospendentes]) Pesquisas RDO </td></tr>";
		$serasa .= "	<tr ><td colspan=9>Nao encontrou registros </td></tr>";
		$serasa .= "</table>";
	}	
	
//echo "<br>log: $log <br>$sql  <br>  $arrserasa[historico] <br> $a $serasa <br> "; //<br><br>datafim $_GET[datafim] <br><br>  data inicio $_GET[datainicio] <br><br> conta principal $_GET[contaprincipal] <br><br> conta $_GET[conta] <br><br> criterio $_GET[criterio]";
echo "<br> $a $serasa $tl "; //<br><br>datafim $_GET[datafim] <br><br>  data inicio $_GET[datainicio] <br><br> conta principal $_GET[contaprincipal] <br><br> conta $_GET[conta] <br><br> criterio $_GET[criterio]";
echo ftela(' 0interrisco_funajax.relatorioagregado');

	
	//********************************************************************
// MOSTRA RELATORIO DE CONSULTADOS E PERMITE REENCAMINHAR A RESPOSTA *
//********************************************************************	
} else if ( $_GET['sq']	== 'relatorioprimeirocarregamento' ) {
	
	$a = '';
	$sql = '';	
	$statusvalidade = '';
    $contador = 0;
    $somatempo = '';
	$horas = Array();
        
		
	if ( true)	{	
		if ( $_GET['todogrupo'] == 'SIM') {
		
			$sql = "
				select grupo 
				from tconta 
				where conta = $_GET[conta]	";
		
			$res = pg_exec($sql);
			
			if ( pg_numrows($res) > 0 ){
				$grupo = pg_result($res,'grupo'); 
			}
			
		}	

		// esta parte monta o relatorio
			
		
						
		$sqlroll = "				
			select distinct
				tchamada.codipessoa,tconta.conta
			from
				tconta,tchamada
			where tchamada.conta = tconta.conta	and			
				tchamada.contaprincipal = $_GET[contaprincipal] and				
				((pescon = 'PES') or (pescon = 'REN')) and
				codipessoa is not null and
				(   (  dataentrada >= cast('$_GET[datainicio] 00:00:00' as timestamp)  )  and (dataentrada <= cast('$_GET[datafim] 23:59:59' as timestamp) ) )and
				tchamada.codipessoa not in ( select tchamada.codipessoa 
									from tchamada,tconta 
									where tchamada.conta = tconta.conta	and			
										tchamada.contaprincipal = $_GET[contaprincipal] and
										tconta.grupoprincipal = 'CSN' and
										((tchamada.pescon = 'PES') or (tchamada.pescon = 'REN')) and
										tchamada.codipessoa is not null and
										tchamada.dataentrada < cast('$_GET[datainicio] 00:00:00' as timestamp)
									)   ";
	
		
		if ($_SESSION['contaprincipal'] == '48813') {
		
			if ( $_GET['todogrupo'] == 'SIM') {
				$sqlroll .= " and tconta.grupo = '$grupo'	";
			} else if ( $_GET['todogrupo'] == 'NAO') {
				$sqlroll .= " and tchamada.conta = '$_GET[conta]'	";
			} else if ( $_GET['todogrupo'] == 'ContaPrincipal') {
				$sqlroll .= " and tchamada.contaprincipal = '$_GET[contaprincipal]'	";
			}		
		
		} else {	
			
			//para pegar o grupoprincipal do cliente
			//para dar mais seguranca e nao puxar liberacao de outro cliente
			$sqlroll .= " and tconta.grupoprincipal = '$_SESSION[grupoprincipal]' ";
			
			if ($_SESSION['nivelativo'] == '29') {
			
				if ( $_GET['todogrupo'] == 'SIM') {
					$sqlroll .= " and tconta.grupo = '$grupo'	";
				} else if ( $_GET['todogrupo'] == 'NAO') {
					$sqlroll .= " and tchamada.conta = '$_GET[conta]'	";
				} else if ( $_GET['todogrupo'] == 'ContaPrincipal') {
					$sqlroll .= " and tchamada.contaprincipal = '$_GET[contaprincipal]'	";
				} else if ( $_GET['todogrupo'] == 'todos') {
					$sqlroll .= " and tchamada.conta = '$_GET[conta]'	";
				}	
			
			} else {
				
				
				if ( $_GET['todogrupo'] == 'SIM') {
					$sqlroll .= " and tconta.grupoprincipal = '$_SESSION[grupoprincipal]' ";
				} else if ( $_GET['todogrupo'] == 'NAO') {
					$sqlroll .= " and tchamada.conta = '$_GET[conta]'	";
				}	
						
			}
		
		}	
						
		if ($_GET['liberado'] == 't') {
			$sqlroll .= " and tchamada.liberado = 't'	";
		} else if ($_GET['liberado'] == 'f') {	
			$sqlroll .= " and tchamada.liberado = 'f'	";
		}	
				
		// classificacao
		$sqlroll .= "	order by tconta.conta ";
			
		$res = pg_exec($sqlroll);

		// esta parte monta o relatorio
		
		if ( pg_numrows($res) > 0 ){
	  
			$a = "<table>";
			$a .= "<tr><td><h3>$_SESSION[login]</td>";
			$a .= "<tr><td> </td>";
			$a .= "<tr><td><h3>Relatorio De Primeiro Carregamento </td>";
			$a .= "<tr><td> </td>";
			$a .= "<tr><td>Classificacao: $_GET[criterio] </td>";
			$a .= "<tr><td>Filtro: Data Inicio $_GET[datainicio] Data fim $_GET[datafim] </td>";			
			$a .= "<tr><td>Data emissao relatorio: ".date('d/m/Y H:i')."</td>";
			$a .= "</table><br>";
	  
			$a .= "<table class='tabla_listado'>";
						
			//if ( $arr['liberado'] == 't') {

				$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Data </td><td class=menuiz_botonoff>Conta </td><td class=menuiz_botonoff> CPF </td><td class=menuiz_botonoff> Nome</td><td class=menuiz_botonoff> Veiculo </td><td class=menuiz_botonoff> Reboque</td><td class=menuiz_botonoff> Semi Reboque</td><td class=menuiz_botonoff>Terceiro Reboque </td><td class=menuiz_botonoff>Usuario </td></tr>";
			//}else{
			//	$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Data </td><td class=menuiz_botonoff> Conta </td><td class=menuiz_botonoff>Ult <br>Sit </td><td class=menuiz_botonoff> Vinculo </td><td class=menuiz_botonoff> Pesquisado </td><td class=menuiz_botonoff colspan=2> Detalhes </td><td class=menuiz_botonoff> Resposta </td></tr>";
			
			//}
			
			for ($i=0; $i < pg_numrows($res ); $i++) {
			
				$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
				
				$sqlultcar = "
					select to_char(tchamada.dataentrada,'DD/MM/YY') as dataentrada,
						tpessoa.nomepessoa,
						tpessoa.cpfcnpj,
						tchamada.usuario,
						tchamada.placacarro,
						tchamada.placareboque,
						tchamada.placasemireboque,
						tchamada.placaterceiroreboque,
						TCONTA.nomeconta
						
					from tchamada,tpessoa,tconta
					where tchamada.codipessoa = $arr[codipessoa]  and
						tchamada.conta = tconta.conta and
						tpessoa.codipessoa = tchamada.codipessoa and
						tchamada.contaprincipal = $_GET[contaprincipal] and
						tconta.grupoprincipal = 'CSN' and
						((tchamada.pescon = 'PES') or (tchamada.pescon = 'REN')) and
						tchamada.codipessoa is not null	 and
						(   (  dataentrada >= cast('$_GET[datainicio] 00:00:00' as timestamp)  )  and (dataentrada <= cast('$_GET[datafim] 23:59:59' as timestamp) ) )  and
						tchamada.codipessoa not in ( select tchamada.codipessoa 
									from tchamada,tconta 
									where tchamada.conta = tconta.conta	and			
										tchamada.contaprincipal = $_GET[contaprincipal] and
										tconta.grupoprincipal = 'CSN' and
										((tchamada.pescon = 'PES') or (tchamada.pescon = 'REN')) and
										tchamada.codipessoa is not null and
										tchamada.dataentrada < cast('$_GET[datainicio] 00:00:00' as timestamp)
									)   ";
									
									
									
				if ($_SESSION['contaprincipal'] == '48813') {
				
					if ( $_GET['todogrupo'] == 'SIM') {
						$sql .= " and tconta.grupo = '$grupo'	";
					} else if ( $_GET['todogrupo'] == 'NAO') {
						$sql .= " and tchamada.conta = '$_GET[conta]'	";
					} else if ( $_GET['todogrupo'] == 'ContaPrincipal') {
						$sql .= " and tchamada.contaprincipal = '$_GET[contaprincipal]'	";
					}		
				
				} else {	
					
					//para pegar o grupoprincipal do cliente
					//para dar mais seguranca e nao puxar liberacao de outro cliente
					$sql .= " and tconta.grupoprincipal = '$_SESSION[grupoprincipal]' ";
					
					if ($_SESSION['nivelativo'] == '29') {
					
						if ( $_GET['todogrupo'] == 'SIM') {
							$sql .= " and tconta.grupo = '$grupo'	";
						} else if ( $_GET['todogrupo'] == 'NAO') {
							$sql .= " and tchamada.conta = '$_GET[conta]'	";
						} else if ( $_GET['todogrupo'] == 'ContaPrincipal') {
							$sql .= " and tchamada.contaprincipal = '$_GET[contaprincipal]'	";
						} else if ( $_GET['todogrupo'] == 'todos') {
							$sql .= " and tchamada.conta = '$_GET[conta]'	";
						}	
					
					} else {
						if ( $_GET['todogrupo'] == 'SIM') {
							$sql .= " and tconta.grupoprincipal = '$_SESSION[grupoprincipal]' ";
						} else if ( $_GET['todogrupo'] == 'NAO') {
							$sql .= " and tchamada.conta = '$_GET[conta]'	";
						}	
						
					}
				
				}	
				
				
				if ($_GET['liberado'] == 't') {
					$sql .= " and tchamada.liberado = 't'	";
				} else if ($_GET['liberado'] == 'f') {	
					$sql .= " and tchamada.liberado = 'f'	";
				}	


				$sql .= "
					order by tchamada.dataentrada desc	
					limit 1
				
				";

				
				$resultcar = pg_exec($sqlultcar);

				// esta parte monta o relatorio
		
				if ( pg_numrows($resultcar) > 0 ){	
	
					$arrultcar = pg_fetch_array($resultcar,0,PGSQL_ASSOC);
	
					$a .= "<tr><td>$arrultcar[dataentrada]</td>
						<td>$arrultcar[nomeconta]</td>
						<td>$arrultcar[cpfcnpj]</td>
						<td>$arrultcar[nomepessoa]</td>
						<td>$arrultcar[placacarro]</td>
						<td>$arrultcar[placareboque]</td>
						<td>$arrultcar[placasemireboque]</td>
						<td>$arrultcar[placaterceiroreboque]</td>
						<td>$arrultcar[usuario]</td>
						</tr>";	
				}	
						
			}
             
            //$somatempo1 =  ($somatempo /$contador) ;        
            
			//$a .= "<tr><td colspan=6>$i Registro(s)</td ><td colspan=5>  Media demora.:".date("H:i:s", array_sum($horas)/count($horas))."</td><td ></td>";
			$a .= "</table> ";
			
		}else{
			$a = faviso2(",","Nao consta registro para o criterio de busca selecionado ");
		}
	
	}
	
	
	
	echo "  $a "; //<br><br>datafim $_GET[datafim] <br><br>  data inicio $_GET[datainicio] <br><br> conta principal $_GET[contaprincipal] <br><br> conta $_GET[conta] <br><br> criterio $_GET[criterio]";

	echo ftela(' 0interrisco_funajax.relatorio primieiro carregamento');

	
} else if ( $_GET['sq']	== 'relatorioagregadovencido' ) {
/*	
	$a = '';
	$sql = '';	
	$statusvalidade = '';	
	$contadorlinha = 0;	
	
	//	$logo ="<img src='../0layout/elastix/$_SESSION[maillogo].png' border='0'  width='100' height='40'>";

	
	//----------------------------------
	// servis
	//----------------------------------
	if ( $_SESSION[maillogo] == 855705){
		$logo ="<img src='../0layout/elastix/$_SESSION[maillogo].png' border='0'  width='100' height='40'>";
		  
	//----------------------------------
	// cci
	//----------------------------------
	} else if ( $_SESSION[contaprincipal] == 908311) {
		$logo ="<img src='../0layout/elastix/logo-cci.gif' border='0'  width='100' height='40'>";
		  
	//----------------------------------
	// lideransat
	//----------------------------------
	} else if ( $_SESSION[contaprincipal] == 871563) {
		$logo ="<img src='../0layout/elastix/logo-lideransat.png' border='0'  width='100' height='40'>";
		
	//----------------------------------
	// Krona
	//----------------------------------
	}else if ( $_SESSION[contaprincipal] == 920636){
		$logo ="<img src='../0layout/elastix/logokrona.jpg' border='0'  width='100' height='40'>";
		
	//----------------------------------
	// Logirisco
	//----------------------------------
	} else if ( $_SESSION[contaprincipal] == 920633) {
		$logo ="<img src='../0layout/elastix/logo-logirisco.png' border='0'  width='100' height='40'>";
		
	//----------------------------------
	// Century
	//----------------------------------
	} else if ( $_SESSION[contaprincipal] == 48813) {

		$logo ="<img src='../0layout/elastix/logo-century.gif' border='0'  width='100' height='40'>";

	} else {
		$logo ="";

	}			
	
	$a .="<table border='0' align=center width='100%' >";
	$a .="<tr><td colspan='3' width='100%'><hr color='#000000' size='2' width='100%'></TD></tr>";
	$a .="<tr><td>$logo </td><td valign='middle'><h2>Relatorio de Agregados/Funcionarios</td></tr>";
	$a .="<tr><td colspan='3' width='100%'><hr color='#000000' size='2' width='100%'></TD></tr>";
	
	//padrao coloco conta
	$sqlgrupoouconta = "  TCONTA.conta = $_GET[conta]  and ";		
	
	if ( $_GET['todogrupo'] == 'SIM') {
	
		$sql = "select grupo from tconta where conta = $_GET[conta]	";
	
		$res = pg_exec($sql);
		
		if ( pg_numrows($res) > 0 )
			$grupo = pg_result($res,'grupo'); 
		
		if ($grupo <> '') {
			$sqlgrupoouconta = "  TCONTA.GRUPO = '$grupo' and ";		
		}else{
			$sqlgrupoouconta = "  TCONTA.conta = $_GET[conta]  and ";		
		}		
	}	
	
	//if ( $_GET['pessoaplaca'] == 'motorista') {
		
	$sql = "  select tchamada.tipovinculo, 
				tpessoa.nomepessoa, 
				tpessoa.codipessoa, 					  
				tpessoa.cpfcnpj, 
				tchamada.validade, 
				tchamada.protocolo, 
				tchamada.conta, 
				tchamada.protocolo, 
				tchamada.liberado,
				tchamada.resposta,
				tconta.nomeconta,
				to_char(tchamada.dataentrada, 'DD/MM/YY') as dataentrada,
				to_char((tchamada.validade - current_timestamp), 'DD') as tempovalidade	
			from tchamada,
				tpessoavinculo,
				tconta,
				tpessoa
			where
				tchamada.codipessoa = tpessoavinculo.codipessoa and
				tchamada.conta = tpessoavinculo.conta and
				$sqlgrupoouconta
				tchamada.conta = tconta.conta and				
				tchamada.codipessoa = tpessoa.codipessoa and
				tchamada.dataentrada = tpessoavinculo.dataentrada and
				((tpessoavinculo.tipovinculo <> 'AJUDANTE') AND	(tpessoavinculo.tipovinculo <> 'AUTONOMO')) and
				cast(to_char((tchamada.validade - current_timestamp), 'DD') as integer) <= $_GET[avencerem]
			order by tchamada.conta,tpessoa.nomepessoa; ";

		$campochave='codipessoa'; 
			
	$res  = pg_exec($sql);

	
//	$a .="<br>$sql<br>grupo=$grupo<br>$sqlg";
	
	if ( pg_numrows($res) > 0 ){

	
		$a .= "<table class='tabla_listado' width='100%'>";
		$a .= "<tr ><td colspan=2>Relatorio Motoristas </td><td colspan=4 align=right>   <a href='#' onclick=desagrega('ckdesagrega');>Desagregar selecionado(s) <img src='../0bmp/setadown.png'></a></td></tr>";
		$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Conta </td><td class=menuiz_botonoff> Vinculo </td><td class=menuiz_botonoff> Pesquisa </td><td class=menuiz_botonoff> Validade Pesquisa </td><td class=menuiz_botonoff> Status Pesquisa</td><td class=menuiz_botonoff>  </td></tr>";
		$a .="<tr><td colspan='6' width='100%'><hr width='100%'></TD></tr>";
		
		for ($i=0; $i < pg_numrows($res ); $i++) {
					
			$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
			
			$contadorregistro = $contadorregistro + 1;	
				
			if ( $arr['tempovalidade'] < 0 ) {
			
				$contadorlinha = $contadorlinha +1;
				$statusvalidade = "<font color='red'>Vencido  $arr[tempovalidade] dias <font>";
				
			}else {

				if ($arr['liberado'] == 't') {
					$statusvalidade = "Liberado $arr[tempovalidade] dias";
					$statusvalidade .= "</td><td> </td>";
				}else if ($arr['liberado'] == 'f') {
					$statusvalidade = "Pendente $arr[tempovalidade] dias";
					$statusvalidade .= "</td><td> $arr[resposta] </td>";
				}else{
					$statusvalidade = "$arr[tempovalidade] dias";
					$statusvalidade .= "</td><td> </td>";
				}				
			}					
						
			$a .= "<tr class='fila_subtotal'><td> $contadorregistro $arr[nomeconta] </td><td > $arr[tipovinculo]  </td> <td>  <b>$arr[nomepessoa] </b> $arr[cpfcnpj] </td><td>$arr[validade]</td> <td>$statusvalidade </td> <td> <input type='checkbox' name='ckdesagrega' id='ckdesagrega' value='delete from tpessoavinculo where conta = $arr[conta] and codipessoa = $arr[codipessoa] '> </a></td></tr>";
				
		}
		
		$a .= "<tr class='letra_gris'><td colspan=7> Encontrados $contadorregistro registro(s) no banco de dados, este relatorio contempla funcionarios e agregados vencidos ou a vencer, caso o motorista nao esteja neste relatorio, provavelmente o vinculo consta como AUTONOMO </td></tr>";
		$a .= "</table>";

	}	
		
	//-----------------------------------------
	// placa carro
	//-----------------------------------------
	$statusvalidade = '';	
	$contadorlinha = 0;
	$contadorregistro = 0;	
	
	$sql = "   select tchamada.tipovinculo, 				
				tchamada.validade, 
				tchamada.conta, 
				tchamada.protocolo, 
				tchamada.liberado, 
				tchamada.resposta,
				tcarrovinculo.placa,
				tconta.nomeconta,
				to_char(tchamada.dataentrada, 'DD/MM/YY') as dataentrada,
				to_char((tchamada.validade - current_timestamp), 'DD') as tempovalidade	
			from tchamada,
				tcarrovinculo,
				tconta
			where
				tchamada.placacarro = tcarrovinculo.placa and
				tchamada.conta = tcarrovinculo.conta and
				$sqlgrupoouconta
				tchamada.conta = tconta.conta and	
				tchamada.pescon <> 'CAN' and				
				tchamada.dataentrada = tcarrovinculo.dataentrada and
				((tcarrovinculo.tipovinculo <> 'AJUDANTE') AND	(tcarrovinculo.tipovinculo <> 'AUTONOMO')) and
				cast(to_char((tchamada.validade - current_timestamp), 'DD') as integer) <= $_GET[avencerem]

			order by tcarrovinculo.conta,tcarrovinculo.placa;  ";
			
	$res  = pg_exec($sql);
//$a .="<br>$sql<br>";
	if ( pg_numrows($res) > 0 ){

		$a .= "<br><table class='tabla_listado' width='100%'>";
		$a .= "<tr ><td colspan=2>Relatorio Veiculos </td><td colspan=4 align=right>  <a href='#' onclick=desagrega('ckdesagrega');>Desagregar selecionado(s) <img src='../0bmp/setadown.png'></a></td></tr>";
		$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Conta </td><td class=menuiz_botonoff> Vinculo </td><td class=menuiz_botonoff> Pesquisa </td><td class=menuiz_botonoff> Validade Pesquisa </td><td class=menuiz_botonoff> Status Pesquisa</td><td class=menuiz_botonoff>  </td></tr>";
		$a .="<tr><td colspan='6' width='100%'><hr width='100%'></TD></tr>";
		
		for ($i=0; $i < pg_numrows($res ); $i++) {
					
			$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
			
			$contadorregistro = $contadorregistro + 1;	
				
			if ( $arr['tempovalidade'] < 0 ) {
			
				$contadorlinha = $contadorlinha +1;
				$statusvalidade = "<font color='red'>Vencido  $arr[tempovalidade] dias <font>";
				
			}else {
				
				if ($arr['liberado'] == 't') {
					$statusvalidade = "Liberado $arr[tempovalidade] dias";
					$statusvalidade .= "</td><td> </td>";
				}else if ($arr['liberado'] == 'f') {
					$statusvalidade = "Pendente $arr[tempovalidade] dias";
					$statusvalidade .= "</td><td> $arr[resposta] </td>";
				}else{
					$statusvalidade = "$arr[tempovalidade] dias";
					$statusvalidade .= "</td><td> </td>";
				}

				
			}					
						
			$a .= "<tr class='fila_subtotal'><td> $contadorregistro $arr[nomeconta] </td><td > $arr[tipovinculo]  </td> <td>  <b>$arr[placa] </b> </td><td>$arr[validade]</td> <td>$statusvalidade </td> <td> <input type='checkbox' name='ckdesagrega' id='ckdesagrega' value='delete from tcarrovinculo where conta = $arr[conta] and placa = @$arr[placa]@ ' > </a></td></tr>";
			
			
		}
		
		$a .= "<tr class='letra_gris'><td colspan=7> Encontrados $contadorregistro registro(s) no banco de dados, este relatorio contempla funcionarios e agregados vencidos ou a vencer, caso o motorista nao esteja neste relatorio, provavelmente o vinculo consta como AUTONOMO </td></tr>";
		$a .= "</table>";

	}	
		
	//-----------------------------------------
	// placa reboque
	//-----------------------------------------
	$statusvalidade = '';	
	$contadorlinha = 0;
	$contadorregistro = 0;	
			
	$sql = "     select tchamada.tipovinculo, 				
				tchamada.validade, 
				tchamada.conta, 
				tchamada.protocolo, 
				tchamada.liberado, 
				tchamada.resposta,
				tcarrovinculo.placa,
				tconta.nomeconta,
				to_char(tchamada.dataentrada, 'DD/MM/YY') as dataentrada,
				to_char((tchamada.validade - current_timestamp), 'DD') as tempovalidade	
			from tchamada,
				tcarrovinculo,
				tconta
			where
				tchamada.placareboque = tcarrovinculo.placa and
				tchamada.conta = tcarrovinculo.conta and
				$sqlgrupoouconta
				tchamada.conta = tconta.conta and		
				tchamada.pescon <> 'CAN' and				
				tchamada.dataentrada = tcarrovinculo.dataentrada and
				((tcarrovinculo.tipovinculo <> 'AJUDANTE') AND	(tcarrovinculo.tipovinculo <> 'AUTONOMO')) and
				cast(to_char((tchamada.validade - current_timestamp), 'DD') as integer) <= $_GET[avencerem]

			order by tcarrovinculo.conta,tcarrovinculo.placa;  ";
			
	$res  = pg_exec($sql);

	if ( pg_numrows($res) > 0 ){

		$a .= "<br><table class='tabla_listado' width='100%'>";
		$a .= "<tr ><td colspan=2>Relatorio Reboque </td><td colspan=4 align=right>  <a href='#' onclick=desagrega('ckdesagrega');>Desagregar selecionado(s) <img src='../0bmp/setadown.png'></a></td></tr>";
		$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Conta </td><td class=menuiz_botonoff> Vinculo </td><td class=menuiz_botonoff> Pesquisa </td><td class=menuiz_botonoff> Validade Pesquisa </td><td class=menuiz_botonoff> Status Pesquisa</td><td class=menuiz_botonoff>  </td></tr>";
		$a .="<tr><td colspan='6' width='100%'><hr width='100%'></TD></tr>";
		
		for ($i=0; $i < pg_numrows($res ); $i++) {
					
			$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
			
			$contadorregistro = $contadorregistro + 1;	
				
			if ( $arr['tempovalidade'] < 0 ) {
			
				$contadorlinha = $contadorlinha +1;
				$statusvalidade = "<font color='red'>Vencido  $arr[tempovalidade] dias <font>";
				
			}else {

				
				if ($arr['liberado'] == 't') {
					$statusvalidade = "Liberado $arr[tempovalidade] dias";
					$statusvalidade .= "</td><td> </td>";
				}else if ($arr['liberado'] == 'f') {
					$statusvalidade = "Pendente $arr[tempovalidade] dias";
					$statusvalidade .= "</td><td> $arr[resposta] </td>";
				}else{
					$statusvalidade = "$arr[tempovalidade] dias";
					$statusvalidade .= "</td><td> </td>";
				}
			
			}					
						
			$a .= "<tr class='fila_subtotal'><td> $contadorregistro $arr[nomeconta] </td><td > $arr[tipovinculo]  </td> <td>  <b>$arr[placa] </b> </td><td>$arr[validade]</td> <td>$statusvalidade </td> <td> <input type='checkbox' name='ckdesagrega' id='ckdesagrega' value='delete from tcarrovinculo where conta = $arr[conta] and placa = @$arr[placa]@ '> </a></td></tr>";
			
		}
		
		$a .= "<tr class='letra_gris'><td colspan=7> Encontrados $contadorregistro registro(s) no banco de dados, este relatorio contempla funcionarios e agregados vencidos ou a vencer, caso o motorista nao esteja neste relatorio, provavelmente o vinculo consta como AUTONOMO </td></tr>";
		$a .= "</table>";

	}			
		
	//-----------------------------------------
	// placa semi-reboque
	//-----------------------------------------
	$statusvalidade = '';	
	$contadorlinha = 0;
	$contadorregistro = 0;	
//echo "tests";	
	
	$sql = "select tchamada.tipovinculo, 				
				tchamada.validade, 
				tchamada.conta, 
				tchamada.protocolo, 
				tchamada.liberado, 
				tcarrovinculo.placa,
				tchamada.resposta,
				tconta.nomeconta,
				to_char(tchamada.dataentrada, 'DD/MM/YY') as dataentrada,
				to_char((tchamada.validade - current_timestamp), 'DD') as tempovalidade	
				
			from tchamada,
				tcarrovinculo,
				tconta
				
			where
				tchamada.placasemireboque = tcarrovinculo.placa and
				tchamada.conta = tcarrovinculo.conta and
				$sqlgrupoouconta
				tchamada.conta = tconta.conta and				
				tchamada.dataentrada = tcarrovinculo.dataentrada and
				tchamada.pescon <> 'CAN' and
				((tcarrovinculo.tipovinculo <> 'AJUDANTE') AND	(tcarrovinculo.tipovinculo <> 'AUTONOMO')) and
				cast(to_char((tchamada.validade - current_timestamp), 'DD') as integer) <= $_GET[avencerem]

			order by tcarrovinculo.conta,tcarrovinculo.placa; ";
			
	$res  = pg_exec($sql);

	if ( pg_numrows($res) > 0 ){

		$a .= "<br><table class='tabla_listado' width='100%'>";
		$a .= "<tr ><td colspan=2>Relatorio Semi-Reboque </td><td colspan=4 align=right>  <a href='#' onclick=desagrega('ckdesagrega');>Desagregar selecionado(s) <img src='../0bmp/setadown.png'></a></td></tr>";
		$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Conta </td><td class=menuiz_botonoff> Vinculo </td><td class=menuiz_botonoff> Pesquisa </td><td class=menuiz_botonoff> Validade Pesquisa </td><td class=menuiz_botonoff> Status Pesquisa</td><td class=menuiz_botonoff>  </td></tr>";
		$a .="<tr><td colspan='6' width='100%'><hr width='100%'></TD></tr>";
		
		for ($i=0; $i < pg_numrows($res ); $i++) {
					
			$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
			
			$contadorregistro = $contadorregistro + 1;	
				
			if ( $arr['tempovalidade'] < 0 ) {
			
				$contadorlinha = $contadorlinha +1;
				$statusvalidade = "<font color='red'>Vencido  $arr[tempovalidade] dias <font>";
				
			}else {
				
				if ($arr['liberado'] == 't') {
					$statusvalidade = "Liberado $arr[tempovalidade] dias";
					$statusvalidade .= "</td><td> </td>";
				}else if ($arr['liberado'] == 'f') {
					$statusvalidade = "Pendente $arr[tempovalidade] dias";
					$statusvalidade .= "</td><td> $arr[resposta] </td>";
				}else{
					$statusvalidade = "$arr[tempovalidade] dias";
					$statusvalidade .= "</td><td> </td>";
				}

								
			}					
						
			$a .= "<tr class='fila_subtotal'><td> $contadorregistro $arr[nomeconta] </td><td > $arr[tipovinculo]  </td> <td>  <b>$arr[placa] </b> </td><td>$arr[validade]</td> <td>$statusvalidade </td> <td> <input type='checkbox' name='ckdesagrega' id='ckdesagrega' value='delete from tcarrovinculo where conta = $arr[conta] and placa = @$arr[placa]@ '> </a></td></tr>";
						
		}
		
		$a .= "<tr class='letra_gris'><td colspan=7> Encontrados $contadorregistro registro(s) no banco de dados, este relatorio contempla funcionarios e agregados vencidos ou a vencer, caso o motorista nao esteja neste relatorio, provavelmente o vinculo consta como AUTONOMO </td></tr>";
		$a .= "</table>";

	}			
	
	//-----------------------------------------
	// placa terceiro-reboque
	//-----------------------------------------
	
	$statusvalidade = '';	
	$contadorlinha = 0;
	$contadorregistro = 0;	
			
	$sql = "     select tchamada.tipovinculo, 				
				tchamada.validade, 
				tchamada.conta, 
				tchamada.protocolo, 
				tchamada.liberado, 
				tcarrovinculo.placa,
				tchamada.resposta,
				tconta.nomeconta,
				to_char(tchamada.dataentrada, 'DD/MM/YY') as dataentrada,
				to_char((tchamada.validade - current_timestamp), 'DD') as tempovalidade	
			from tchamada,
				tcarrovinculo,
				tconta
			where
				tchamada.placaterceiroreboque = tcarrovinculo.placa and
				tchamada.conta = tcarrovinculo.conta and
				$sqlgrupoouconta
				tchamada.conta = tconta.conta and				
				tchamada.dataentrada = tcarrovinculo.dataentrada and
				tchamada.pescon <> 'CAN' and
				((tcarrovinculo.tipovinculo <> 'AJUDANTE') AND	(tcarrovinculo.tipovinculo <> 'AUTONOMO')) and
				cast(to_char((tchamada.validade - current_timestamp), 'DD') as integer) <= $_GET[avencerem]

			order by tcarrovinculo.conta,tcarrovinculo.placa; ";
			
	$res  = pg_exec($sql);

	if ( pg_numrows($res) > 0 ){

		$a .= "<br><table class='tabla_listado' width='100%'>";
		$a .= "<tr ><td colspan=2>Relatorio terceiro-Reboque </td><td colspan=4 align=right>  <a href='#' onclick=desagrega('ckdesagrega');>Desagregar selecionado(s) <img src='../0bmp/setadown.png'></a></td></tr>";
		$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Conta </td><td class=menuiz_botonoff> Vinculo </td><td class=menuiz_botonoff> Pesquisa </td><td class=menuiz_botonoff> Validade Pesquisa </td><td class=menuiz_botonoff> Status Pesquisa</td><td class=menuiz_botonoff>  </td></tr>";
		$a .="<tr><td colspan='6' width='100%'><hr width='100%'></TD></tr>";
		
		for ($i=0; $i < pg_numrows($res ); $i++) {
					
			$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
			
			$contadorregistro = $contadorregistro + 1;	
				
			if ( $arr['tempovalidade'] < 0 ) {
			
				$contadorlinha = $contadorlinha +1;
				$statusvalidade = "<font color='red'>Vencido  $arr[tempovalidade] dias <font>";
				
			}else {

				
				if ($arr['liberado'] == 't') {
					$statusvalidade = "Liberado $arr[tempovalidade] dias";
					$statusvalidade .= "</td><td> </td>";
				}else if ($arr['liberado'] == 'f') {
					$statusvalidade = "Pendente $arr[tempovalidade] dias";
					$statusvalidade .= "</td><td> $arr[resposta] </td>";
				}else{
					$statusvalidade = "$arr[tempovalidade] dias";
					$statusvalidade .= "</td><td> </td>";
				}
								
			}					
						
			$a .= "<tr class='fila_subtotal'><td> $contadorregistro $arr[nomeconta] </td><td > $arr[tipovinculo]  </td> <td>  <b>$arr[placa] </b> </td><td>$arr[validade]</td> <td>$statusvalidade </td> <td> <input type='checkbox' name='ckdesagrega' id='ckdesagrega' value='delete from tcarrovinculo where conta = $arr[conta] and placa = @$arr[placa]@ '> </a></td></tr>";
						
		}
		
		$a .= "<tr class='letra_gris'><td colspan=7> Encontrados $contadorregistro registro(s) no banco de dados, este relatorio contempla funcionarios e agregados vencidos ou a vencer, caso o motorista nao esteja neste relatorio, provavelmente o vinculo consta como AUTONOMO </td></tr>";
		$a .= "</table>";

	}			
			
	$a.=  "<BR><BR><table align=center><tr><td class=menuiz_botonoff><fieldset><legend><b>  Legenda - tipos de vinculo de trabalho </b></legend>  FUNCIONARIO = Motorista frota com carteira de trabalho assinada <br> INTERNO = Funcionario com funcoes internas ex.: administrativo/expedicao <br> AGREGADO = Contrato de agregado ou pelo menos 12 carregamentos no ano  <br> AUTONOMO = Sem nenhum vinculo, transporte eventual <br> AJUDANTE = Auxiliar de carregamento/descarregamento / Chapa</fieldset></td></tr></table>";
	
	$a.= ftela(' 0interrisco_funajax.relatorioagregadovencido');
	
	//echo " $a  ";
	//echo " $a  <br>SQLBUSCA FILIAIS <br> sql = $sql <br>SQLVINCULO<br> $sqlvinculo  ";
	echo " $a ";
	

	
// deleta agregado de tchamada	
} else if ( $_GET['sq']	== 'desagrega' ) {
	
	//echo $_GET['sql'];
	$res  = pg_exec($_GET['sql']);
		
*/

/////////////////////			
		
//********************************************************************
// MOSTRA RELATORIO DE CONSULTADOS E PERMITE REENCAMINHAR A RESPOSTA *
//********************************************************************	
} else if ( $_GET['sq']	== 'relatorioavisosgeral' ) {

	$a = '';
	$sql = '';	
	$statusvalidade = '';
	$cpf = '';
	$nomepessoa = '';

	$sql = "				
		select 
			*			
		from
			tocorrencia ";
			
    $sql .= " where  (   (  datacriacao >= cast('$_GET[datainicio]' as date)  )  and (datacriacao <= cast('$_GET[datafim]' as date) ) ) ";
			
	// classificacao
	$sql .= "	order by datacriacao";
		
	$res = pg_exec($sql);

	// esta parte monta o relatorio
	
	if ( pg_numrows($res) > 0 ){
  
		$a = "<table class='tabla_listado'>";
		$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Data </td><td class=menuiz_botonoff> Usuario </td><td class=menuiz_botonoff> obs </td><td class=menuiz_botonoff> chavedebusca </td><td>CPF - Nomepessoa</td></tr>";
		
		for ($i=0; $i < pg_numrows($res ); $i++) {

				$cpf = '';
				$nomepessoa = '';
		
			$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);


///////
			$sqlpes = "				
				select 
					*			
				from
					tpessoa ";
					
			$sqlpes .= " where  tpessoa.codipessoa = $arr[chavedebusca] ";
					
			// classificacao
			$sqlpes .= "	order by datacriacao";
				
			$rescpf = pg_exec($sqlpes);

			if ( pg_numrows($rescpf) > 0 ){
  	
				$arrcpf = pg_fetch_array($rescpf,0,PGSQL_ASSOC);
				$cpf = $arrcpf['cpfcnpj'];
				$nomepessoa = $arrcpf['nomepessoa'];
				
			}	

			

///////


			
				// verifica se � autonomo, nao deve colocar validade da ficha 
			$a .= "<tr class='fila_subtotal'><td> $arr[datacriacao]</td><td > $arr[usuario]</td><td>$arr[obs] </td><td> $arr[chavedebusca]  </td> </td>$cpf $nomepessoa</tr>";
			
		}	
		
		$a .= "</table>";
		
	}else{
		$a = faviso2(",","Nao consta registro para o criterio de busca selecionado ");
	}

	$a .= "<tr class='fila_subtotal'><td> total</td><td > $i</td><td>registros </td><td>  </td> </td></tr>";
	
	echo " $a ";//<br><br> sql = $sql <br><br>datafim $_GET[datafim] <br><br>  data inicio $_GET[datainicio] <br><br> conta principal $_GET[contaprincipal] <br><br> conta $_GET[conta] <br><br> criterio $_GET[criterio]";

//echo 'teste';	
	
	
//********************************************************************
// MOSTRA RELATORIO DE CONSULTADOS E PERMITE REENCAMINHAR A RESPOSTA *
//********************************************************************	
} else if ( $_GET['sq']	== 'relatoriocustos' ) {

	$a = '';
	$sql = '';
	
	
	$sql = "				
		select 
			tchamada.senha,
			tchamada.placacarro,
			tchamada.placareboque,
			tchamada.placasemireboque,			
			tchamada.placaterceiroreboque,
			tconta.nomeconta,
			tpessoa.nomepessoa,
			tchamada.usuario,
			tchamada.validade,
			tchamada.statusprotocolo,		
			tchamada.liberado,	
			tchamada.custo,
			tchamada.conta,
			tchamada.contaprincipal,
			tpessoa.cpfcnpj,
			to_char(dataentrada, 'DD/MM/YY') as dataentrada	
		from
			tconta,
			tchamada LEFT OUTER JOIN tpessoa ON (tchamada.codipessoa = tpessoa.codipessoa)
		where tchamada.conta = tconta.conta  ";

	// se o usuario e century entao da para consultar
	// todas as filiais, se nao for century so permite abrir
	// os cadastros da filial
	
	if ( $_SESSION['contaprincipal'] == '48813') {
		$sql .= " and tchamada.contaprincipal = $_GET[contaprincipal]	";

		if ($_GET['conta'] != '') {
			$sql .= " and tchamada.conta = $_GET[conta]	";			
		}

	}else{
		$sql .= " and tchamada.contaprincipal = $_GET[contaprincipal]	";
		$sql .= " and tchamada.conta = $_GET[conta]	";
	}	

	if ($_GET['criterio'] == 'data') {
		$sql .= " and (   (  dataentrada >= cast('$_GET[datainicio]' as date)  )  and (dataentrada <= cast('$_GET[datafim]' as date) ) ) ";
	}else if ($_GET['criterio'] == 'dia') {
		$sql .= "	and datasaida >= CURRENT_DATE ";
	}	

	$sql .= "	order by $_GET[classificacao] ";

	if ($_GET['criterio'] == '10') {
		$sql .= " limit 10 ";
	}else {
		$sql .= " limit 2000 ";
	}	
		
	$res = pg_exec($sql);

	// esta parte monta o relatorio
	
	if ( pg_numrows($res) > 0 ){
 
		$a = "<table class='tabla_listado'>";
		$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Data </td><td class=menuiz_botonoff> Conta </td><td class=menuiz_botonoff> Sit </td><td class=menuiz_botonoff> Senha </td><td class=menuiz_botonoff> Pesquisa </td><td class=menuiz_botonoff> Val. Pesquisa* </td><td class=menuiz_botonoff> Resposta </td></tr>";
		
		for ($i=0; $i < pg_numrows($res ); $i++) {
		
			$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);

			if ( $arr['liberado'] == 't') {
				$a .= "<tr class='fila_subtotal'><td> $arr[dataentrada]</td><td > $arr[nomeconta]</td><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'></td><td>$arr[senha] </td><td> $arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque]  $arr[placaterceiroreboque] </td><td>$arr[validade]</td> <td>$arr[custo]</td></tr>";
			}else{
				$a .= "<tr class='fila_subtotal'><td> $arr[dataentrada]</td><td > $arr[nomeconta]</td><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'></td><td align=center> --- </td><td> $arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] $arr[placaterceiroreboque]</td><td align=center> --- </td> <td>$arr[custo]</td></tr>";
			}	
		}
		$a .= "<tr class='letra_gris'><td colspan=7> $i Registro(s) *Obs.: Independente da validade da pesquisa, para motoristas autonomos necessita uma consulta a cada carregamento ! </td></tr>";
		$a .= "</table>";
		
	}else{
		$a = faviso2(",","Nao consta registro para o criterio de busca selecionado ");
	}
	
	echo " $a ";//<br><br> sql = $sql ";// <br><br>datafim $_GET[datafim] <br><br>  data inicio $_GET[datainicio] <br><br> conta principal $_GET[contaprincipal] <br><br> conta $_GET[conta] <br><br> criterio $_GET[criterio]";
	
//********************************************************************
// MOSTRA RELATORIO DE CONSULTADOS E PERMITE REENCAMINHAR A RESPOSTA *
//********************************************************************	

//esse � o da lupinha
} else if ( $_GET['sq']	== 'relatoriopesquisadosporcpfnome' ) {

	$a = '';
	$sql = '';	

	$sql = "				
		select 
			tchamada.senha,
			tchamada.placacarro,
			tchamada.placareboque,
			tchamada.placasemireboque,
			tchamada.placaterceiroreboque,			
			tconta.nomeconta,
			tpessoa.nomepessoa,
			tchamada.usuario,
			tchamada.validade,
			tchamada.statusprotocolo,		
			tchamada.liberado,	
			tchamada.conta,
			tchamada.codipessoa,
			tchamada.contaprincipal,
			tchamada.responsavel,
			tpessoa.copiadoc,
			tpessoa.cpfcnpj,
			to_char((tchamada.validade - current_timestamp), 'DD') as tempovalidade,	
			to_char(dataentrada, 'DD/MM/YY') as dataentrada
		from
			tconta,
			tchamada LEFT OUTER JOIN tpessoa ON (tchamada.codipessoa = tpessoa.codipessoa)
		where 
			tchamada.conta = tconta.conta and 
			tchamada.pescon != 'CAN'
		";


	//se operador >= 16 ve grupo
	
	//se operador >= 17 ve grupo principal
	
	//se operador >= 29 ve conta principal
	
	//se operador >= 30 ve todas as contas principais

	//$_SESSION['grupo'] e $_SESSION['contaprincipal'] nao pode ser vazio senao '' acha que � um grupo
	// coloqueio ativogrupoprincipal que deu zebra at� atualizar todos os usuarios
	
	$log = '';
	
	
	
	if ( $_SESSION['nivelativo'] >= 30  && $_SESSION['contaprincipal'] == '48813' ) {

		//nao faco nada pq vejo todas as contas principais
		$log .= '1';
		
	} else if ( $_SESSION['nivelativo'] >= 29 && $_SESSION['contaprincipal'] != '' ) {

		$sql .= " and tchamada.contaprincipal = $_GET[contaprincipal] ";
		$log .= '2';	
	
	} else if ( $_SESSION['nivelativo'] >= 17  && $_SESSION['grupoprincipal'] != '' || $_SESSION['ativogrupoprincipal'] == 't' ) {
		
		$sql .= " and tconta.grupoprincipal = '$_SESSION[grupoprincipal]'	";	
		$log .= '3';
		
	} else if ( $_SESSION['nivelativo'] >= 16  && $_SESSION['grupo'] != '' ) {	
	
		$sql .= " and tconta.grupo = '$_SESSION[grupo]'	";			
		$log .= '4';	
		
	} else {
			
		$sql .= " and tchamada.conta = '$_GET[conta]'	";
		$log .= '5';
	}
		
		
	if ($_GET['criterio'] == 'cpf') {

		$arrcpfnome['codipessoa'] = 0;
	
		$sqlcpfnome = "
			select codipessoa
			from tpessoa 
			where tpessoa.cpfcnpj = '$_GET[chave]' ";

		$ressqlcpfnome = pg_exec($sqlcpfnome);

		// esta parte monta o relatorio
		
		if ( pg_numrows($ressqlcpfnome) > 0 ){
	  
			$arrcpfnome = pg_fetch_array($ressqlcpfnome,0,PGSQL_ASSOC);
		
		}	
		
		$sql .= " and tchamada.codipessoa = $arrcpfnome[codipessoa] ";		
			
	} else 	if ($_GET['criterio'] == 'nome') {

		$arrcpfnome['codipessoa'] = 0;
	
		$sqlcpfnome = "
			select codipessoa
			from tpessoa 
			where trim(nomepessoa) like '$_GET[chave]%' ";

			$ressqlcpfnome = pg_exec($sqlcpfnome);

		// esta parte monta o relatorio
		
		if ( pg_numrows($ressqlcpfnome) > 0 ){
	  
			$arrcpfnome = pg_fetch_array($ressqlcpfnome,0,PGSQL_ASSOC);
		
		}	
		
		$sql .= " and tchamada.codipessoa = $arrcpfnome[codipessoa] ";		
		
	}	else 	if ($_GET['criterio'] == 'placacarro') {
	
		$sql .= " and  tchamada.placacarro = '$_GET[chave]' ";
		
	}	else	if ($_GET['criterio'] == 'placareboque') {
	
		$sql .= " and  tchamada.placareboque = '$_GET[chave]' ";
		
	}	else 	if ($_GET['criterio'] == 'placasemireboque') {
	
		$sql .= " and  tchamada.placasemireboque = '$_GET[chave]' ";
		
	}	else 	if ($_GET['criterio'] == 'placaterceiroreboque') {
	
		$sql .= " and  tchamada.placaterceiroreboque = '$_GET[chave]' ";		
		
	}	
		
	$sql .= "order by liberado desc,datasaida desc";

	$sql .= " limit 400 ";

//$f = fopen('log_relatoriopesquisadosporcpfnome.txt', 'w');
//fwrite($f,"\n sql: \n $sql 
//	\n nivelativo $_SESSION[nivelativo]
//	\n todogrupo $_GET[todogrupo] 
//	\n conta principal $_SESSION[contaprincipal] 
//	\n grupo $_SESSION[grupo] 
//	\n grupoprincipal $_SESSION[grupoprincipal] 
//	\n get conta $_GET[conta] 
//	\n ativogrupoprincipal = $_SESSION[ativogrupoprincipal] 
//	\n log $log " );		
//fclose($f);	
		
	$res = pg_exec($sql);

	// esta parte monta o relatorio
	//	echo "<br><br>$sql<br>  ";
			
	if ( pg_numrows($res) > 0 ){
  
		$a = "<table class='tabla_listado' width='100%'>";
		$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Data </td><td class=menuiz_botonoff> Conta </td><td class=menuiz_botonoff> Sit </td><td class=menuiz_botonoff> Senha </td><td class=menuiz_botonoff> Pesquisa </td><td class=menuiz_botonoff> Valido </td><td class=menuiz_botonoff>Dias</td><td class=menuiz_botonoff> Resposta </td><td class=menuiz_botonoff> Upload </td></tr>";
		
		for ($i=0; $i < pg_numrows($res ); $i++) {
		
			$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
			
			$negativo='';
			$vencido = '';	
			if ( (int)$arr[tempovalidade] < 1 ) {
				$negativo = "<font color=clred>";
				$vencido='vencido';
			}
					
			if ( $arr['liberado'] == 't') {				
				$a .= "<tr class='fila_subtotal'><td> $arr[dataentrada]</td><td > $arr[nomeconta] $arr[responsavel]</td><td><img src='../0bmp/$arr[liberado]a$vencido.png' align='absmiddle' border='0' width='25' height='25'></td><td>$arr[senha] </td><td> <B> $arr[cpfcnpj] $arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] $arr[placaterceiroreboque] </b></td><td>$negativo$arr[validade]</td><td align=center> $negativo $arr[tempovalidade]</td> <td  class='botonoff'><a href='#' onclick=\"mostraresposta('$arr[senha]');\"><div id='buttonaz'><img src='../0bmp/pasta.png' align='absmiddle' border='0' width='25' height='25'> Visualizar</div></a></td>";
			}else{
				$a .= "<tr class='fila_subtotal'><td> $arr[dataentrada]</td><td > $arr[nomeconta] $arr[responsavel]</td><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'></td><td align=center> --- </td><td> $arr[cpfcnpj] $arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] $arr[placaterceiroreboque] </td><td align=center> --- </td><td> --- </td> <td class='botonoff'><a href='#' onclick=\"mostraresposta('$arr[senha]');\"><div id='buttonaz'><img src='../0bmp/pasta.png' align='absmiddle' border='0' width='25' height='25'> Visualizar</div></a></td>";
			}	
			
//			$a .= "<td class='botonoff'> <a href='#' onclick=uploaddocumentos('$arr[cpfcnpj]','$arr[codipessoa]','$arr[placacarro]','$arr[placareboque]','$arr[placasemireboque]','$arr[placaterceiroreboque]')><img src='../0bmp/upload.png' width='40' height='40'  > </a>  </td><td> $arr[copiadoc]</tr></tr>";
			$a .= "<td class='botonoff'> <a href='#' onclick=uploaddocumentos('$arr[cpfcnpj]','$arr[codipessoa]','$arr[placacarro]','$arr[placareboque]','$arr[placasemireboque]','$arr[placaterceiroreboque]')><img src='../0bmp/upload.png' width='40' height='40'  > </a>  </td></tr>";							
			// funcao uploaddocumentos() est� na pasta /funcoes/telauploadmotoristaveiculo.js e incluido em /0interrisco/registrospendentes.php
		
		}
		
		$a .= "<tr class='letra_gris'><td colspan=7> $i Registro(s) *Obs.: Independente da validade da pesquisa, para motoristas autonomos necessita uma consulta a cada carregamento ! </td></tr>";		
		$a .= "</table> *-*";
		
	}else{
		$a = faviso2("-","Nao consta registro para o criterio de busca selecionado ");
	}
	
//	echo " $a <br><br>  $sql <br><br> $sqlcpfnome  ";//<br><br> $sql ";//<br><br> $_GET[datafim] <br><br>  $_GET[datainicio] <br><br>  $_GET[contaprincipal] <br><br>  $_GET[conta] <br><br> $_GET[criterio]";

	$a.= ftela(' 0interrisco_funajax.relatoriopesquisadosporcpfnome');

	echo " $a ";

} else if ( $_GET['sq']	== 'relatoriomotoristasliberados' ) {

	$a = '';
	$sql = '';	

	$sql = "				
		select 
			tchamada.senha,
			tchamada.placacarro,
			tchamada.placareboque,
			tchamada.placasemireboque,
			tchamada.placaterceiroreboque,
			tconta.nomeconta,
			tconta.grupo,
			tpessoa.nomepessoa,
			tchamada.usuario,
			tchamada.validade,
			tchamada.statusprotocolo,		
			tchamada.liberado,	
			tchamada.conta,
			tchamada.contaprincipal,
			tchamada.tipovinculo,
			tpessoa.cpfcnpj,
			to_char(dataentrada, 'DD/MM/YY') as dataentrada,
			to_char((current_timestamp - dataentrada), 'DD') as tempodataentrada,
			to_char((tchamada.validade - current_timestamp), 'DD') as tempovalidade
		from
			tconta,
			tchamada LEFT OUTER JOIN tpessoa ON (tchamada.codipessoa = tpessoa.codipessoa)
		where tchamada.conta = tconta.conta and
			tchamada.pescon != 'CAN' and
			tchamada.conta = $_GET[conta]  			";

	// nao verifico se e centuru pq esta tela serve para ver se o usuario foi liberado ou nao
	

	if ($_GET['criterio'] == 'cpfcnpj') {

		$tipopesquisa = 'Nome motorista';
		
		$arrcpfnome['codipessoa'] = 0;
	
		$sqlcpfnome = "
			select codipessoa
			from tpessoa 
			where tpessoa.cpfcnpj = '$_GET[cpfcnpj]' ";

		$ressqlcpfnome = pg_exec($sqlcpfnome);

		// esta parte monta o relatorio
		
		if ( pg_numrows($ressqlcpfnome) > 0 ){
	  
			$arrcpfnome = pg_fetch_array($ressqlcpfnome,0,PGSQL_ASSOC);
		
		}	
		
		$sql .= " and tchamada.codipessoa = $arrcpfnome[codipessoa] ";		
			
	} else 	if ($_GET['criterio'] == 'nome') {

		$arrcpfnome['codipessoa'] = 0;
	
		$sqlcpfnome = "
			select codipessoa
			from tpessoa 
			where trim(nomepessoa) like '$_GET[chave]%' ";

			$ressqlcpfnome = pg_exec($sqlcpfnome);

		// esta parte monta o relatorio
		
		if ( pg_numrows($ressqlcpfnome) > 0 ){
	  
			$arrcpfnome = pg_fetch_array($ressqlcpfnome,0,PGSQL_ASSOC);
		
		}	
		
		$sql .= " and tchamada.codipessoa = $arrcpfnome[codipessoa] ";		
			
	}	else 	if ($_GET['criterio'] == 'placacarro') {
	
		$tipopesquisa = 'Placa veiculo';
		$sql .= " and  tchamada.placacarro = '$_GET[placacarro]' ";
		
	}	else	if ($_GET['criterio'] == 'placareboque') {
	
		$tipopesquisa = 'Placa reboque';
		$sql .= " and  tchamada.placareboque = '$_GET[placareboque]' ";
		
	}	else 	if ($_GET['criterio'] == 'placasemireboque') {
	
		$tipopesquisa = 'Placa semi reboque';
		$sql .= " and  tchamada.placasemireboque = '$_GET[placasemireboque]' ";

	}	else 	if ($_GET['criterio'] == 'placaterceiroreboque') {
	
		$tipopesquisa = 'Placa terceiro reboque';
		$sql .= " and  tchamada.placaterceiroreboque = '$_GET[placaterceiroreboque]' ";
		
	}	
		
	$sql .= "	order by protocolo desc ";

	//$sql .= " limit 500 ";
		
	$res = pg_exec($sql);

	// esta parte monta o relatorio

	if ( pg_numrows($res) > 0 ){
  
		$a = "<table class='redonda' width=100% >";
		$a .= "<tr><td>  </td><td align=center> Data </td><td> $tipopesquisa </td><td align=center> Senha </td></tr>";
						
			$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
						
			if ($_GET['criterio'] == 'cpfcnpj') {
			
				$registropesquisado = "<b> $arr[nomepessoa] </b>";
				
			}	else 	if ($_GET['criterio'] == 'placacarro') {
			
				$registropesquisado = "<b> $arr[placacarro] </b>";
					
			}	else	if ($_GET['criterio'] == 'placareboque') {
			
				$registropesquisado = "<b> $arr[placareboque] </b>";
								
			}	else 	if ($_GET['criterio'] == 'placasemireboque') {
			
				$registropesquisado = "<b> $arr[placasemireboque] </b>";
				
			}	else 	if ($_GET['criterio'] == 'placaterceiroreboque') {
			
				$registropesquisado = "<b> $arr[placaterceiroreboque] </b>";
				
			}				
			
			// se ta liberado
			if ( $arr['liberado'] == 't') {
			
				// se ainda ta valido
				if ($arr['tempovalidade'] > 0) {
				
					//se � autonomo ou adudante
					if ($arr['tipovinculo'] == 'AUTONOMO' || $arr['tipovinculo'] == 'AJUDANTE' ){

						// se � da 3 coracoes grupo "tres corac" liberado para 30 dias
						$intconta = (int)$arr['conta'];
				
						if (  ($_GET['grupo'] == '3CORACOES' ) && ($intconta >= 65372 && $intconta <= 65386 ) || ($intconta == 1509181115) ) {

							IF ( $arr['tempodataentrada'] >= 0 && $arr['tempodataentrada'] <= 30 ) {
								$a .= "<tr class='fila_paginacion' ><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='20' height='20'> <br>Apto</td><td> <font size=4> ".date('d/m/y')."</td><td><font size=3> $registropesquisado </font></td><td>$arr[senha] </td> </tr>";						
							}else{	
								$a .= "<tr class='fila_paginacion' ><td><img src='../0bmp/fa.png' align='absmiddle' border='0' width='20' height='20'> <br>Pendente</td><td> <font size=4> </td><td><font size=3> $registropesquisado <br> $arr[tipovinculo] - Necessita nova liberacao a cada trinta dias </font></td><td> </td> </tr>";
							}
						}else{
							//se nao for da 3 coracoes tem que fazer nova consulta
							$a .= "<tr class='fila_paginacion' ><td><img src='../0bmp/fa.png' align='absmiddle' border='0' width='20' height='20'> <br>Pendente</td><td> <font size=4> </td><td><font size=3> $registropesquisado <br> $arr[tipovinculo] - Necessita nova liberacao a cada carregamento </font></td><td> </td> </tr>";
						}
					
					}else{
						//liberado normal
						$a .= "<tr class='fila_paginacion' ><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='20' height='20'> <br>Apto</td><td> <font size=4> ".date('d/m/y')."</td><td><font size=3> $registropesquisado </font></td><td>$arr[senha] </td> </tr>";
					}
					

					
					
				}else{
					//negativado
					$a .= "<tr class='fila_paginacion' ><td><img src='../0bmp/fa.png' align='absmiddle' border='0' width='20' height='20'> <br>Vencido</td><td> <font size=4> ".date('d/m/y')."</td><td><font size=3> $registropesquisado </font></td><td> ---  </td> </tr>";				
				}	
				
			}else{
				$a .= "<tr class='fila_subtotal'><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='20' height='20'> <br> Pendente </td><td> <font size=4> $arr[dataentrada]</td><td> <font size=3> $registropesquisado </td><td align=center> --- </td></tr>";
			}		
		
		$a .= "</table>";
		
	}else{
		//$a = faviso2("-","Nao consta registro para o criterio de busca selecionado ");
		$a = "<table><tr class='redonda'><td align=center> <img src='../0bmp/a.png' align='absmiddle' border='0' width='25' height='25'> Sr. Usuario, registro sem cadastro no sitema ! <br> Por favor, efetuar cadastramento. <td></tr></table>";
		$semcadastro = ',SEM CAD';
	}

	
	$sqlacesso = "
		insert into tcontroleacesso (data,usuario,tela,obs)	
		values	('".date("d/m/Y H:i:s")."','$_SESSION[usuario]','Liberados','$_GET[cpfcnpj] $registropesquisado $semcadastro'    );
	";
	
	$resacesso = pg_exec($sqlacesso);
		
//	$a .= "$sql ***tempovalidade $arr[tempovalidade]  ***tempodataentrada $arr[tempodataentrada] ";	
		
	echo " $a ";



} else if ( $_GET['sq']	== 'relatorioacessos' ) {

	$a = '';
	$sql = '';	

	$sql = "				
		select 
			tcontroleacesso.data,			
			tcontroleacesso.obs,
			tcontroleacesso.usuario,
			tconta.nomeconta			
		from
			tcontroleacesso,
			tusuario,
			tconta
		where tcontroleacesso.tela = '$_GET[tela]' and
			tcontroleacesso.usuario = tusuario.usuario and			
			tusuario.contaprincipal = '$_GET[conta]' and	
			tconta.conta = 	tusuario.conta and	
			(( tcontroleacesso.data >= cast('$_GET[datainicio]' as date)  )  and (tcontroleacesso.data <= cast('$_GET[datafim]' as date) ) )
		";
			
	$sql .= " order by usuario,data desc ";

	$sql .= " limit 2000 ";
		
	$res = pg_exec($sql);


	if ( pg_numrows($res) > 0 ){
  
		$a = "<table  class='tabla_listado' width=100% >";
		$a .= "<tr><td  class=menuiz_botonoff>Id</td><td  class=menuiz_botonoff> Data </td><td  class=menuiz_botonoff> Cliente </td><td class=menuiz_botonoff> Usuario </td><td align=center class=menuiz_botonoff> Obs (CPF / Veiculo ) </td></tr>";

		for ($i=0; $i < pg_numrows($res ); $i++) {
		
			$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
						
			$a .= "<tr class='fila_paginacion' ><td> ".($i+1)."</td><td>  $arr[data] </td><td>  $arr[nomeconta] </td><td> $arr[usuario]  </td><td> $arr[obs] </td></tr>";
			
		}
		
		$a .= "<TR><td colspan=4 class=menuiz_botonoff>* Limte de 2000 registros, (SEM CAD) = informado na consulta que nao tem cadastro no sistema </td></table>";
	
		
	}else{
	
		//$a = faviso2("-","Nao consta registro para o criterio de busca selecionado ");
		$a = "<table><tr class='redonda'><td align=center> <img src='../0bmp/a.png' align='absmiddle' border='0' width='25' height='25'> Sr. Usuario, nao encontrei registros ! <br> Por favor verifique os criterios de busca <td></tr></table>";

	}
	
		
	echo " $a ";

	
//**********************************************************
// mostra a resposta caso o cliente queira gerar novamente *
//**********************************************************	
} else if ( $_GET['sq']	== 'mostraresposta' ) { 
  
	echo exiberesposta($_GET['senha']);   
	
//***********************************************************
// mostra o historico de consultas efetuadas pelo motorista *
//***********************************************************	
//tela para mostrar o historico pedido pelo rony que foi substituido pela tela formulariopreliberacao.php
} else if ( $_GET['sq']	== 'historico' ) { 

	$sql = '';		
	$a = '';

	$sql = "			
		select 
			tchamada.protocolo,
			tchamada.placacarro,
			tchamada.placareboque,
			tchamada.placasemireboque,
			tchamada.placaterceiroreboque,
			tconta.nomeconta,
			tpessoa.nomepessoa,
			tchamada.usuario,
			tchamada.statusprotocolo,			
			tchamada.liberado,	
			tchamada.senha,			
			tchamada.validade,
			tchamada.conta,
			tchamada.contaprincipal,
			to_char(dataentrada, 'DD/MM/YY') as dataentrada	
		from
			tconta,
			tchamada LEFT OUTER JOIN tpessoa ON (tchamada.codipessoa = tpessoa.codipessoa)
		where tchamada.conta = tconta.conta ";
								
    if ($_GET['codipessoa'] <> ''){
		$sql .= " and tpessoa.codipessoa = $_GET[codipessoa] ";			
	}

    if ($_GET['placacarro'] <> ''){
		$sql .= " and placacarro = $_GET[placacarro] ";			
	}
	
    if ($_GET['placareboque'] <> ''){
		$sql .= " and placareboque = $_GET[placareboque] ";			
	}
	
    if ($_GET['placasemireboque'] <> ''){
		$sql .= " and placasemireboque = $_GET[placasemireboque] ";			
	}

    if ($_GET['placaterceiroreboque'] <> ''){
		$sql .= " and placaterceiroreboque = $_GET[placaterceiroreboque] ";			
	}
	
	
	$res = pg_exec($sql);
	
	if ( pg_numrows($res) > 0 ){
	
		$a = "<table class='tabla_listado'>";
		$a .= "<tr class='letra_gris'><td class=menuiz_botonoff> Data </td><td class=menuiz_botonoff> Conta </td><td class=menuiz_botonoff> Sit </td><td class=menuiz_botonoff> Senha </td><td class=menuiz_botonoff> Pesquisa </td><td class=menuiz_botonoff> Val. Pesquisa* </td><td class=menuiz_botonoff> Resposta </td></tr>";
		
	    for ($i=0; $i < pg_numrows($res ); $i++) {
 
			$arr=pg_fetch_array($res,$i,PGSQL_ASSOC);
		  	
			if ( $arr['liberado'] == 't') {
				$a .= "<tr class='fila_paginacion'><td> $arr[dataentrada]</td><td > $arr[nomeconta]</td><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'></td><td>$arr[senha] </td><td> $arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] $arr[placaterceiroreboque] </td><td>$arr[validade]</td> <td><a href='#' onclick=\"mostraresposta('$arr[senha]');\" ><img src='../0bmp/pasta.png' align='absmiddle' border='0' width='25' height='25'>Ver </a></td></tr>";
			}else{
				$a .= "<tr class='fila_subtotal'><td> $arr[dataentrada]</td><td > $arr[nomeconta]</td><td><img src='../0bmp/$arr[liberado]a.png' align='absmiddle' border='0' width='25' height='25'></td><td align=center> --- </td><td> $arr[nomepessoa] $arr[placacarro] $arr[placareboque] $arr[placasemireboque] $arr[placaterceiroreboque]</td><td align=center> --- </td> <td><a href='#' onclick=\"mostraresposta('$arr[senha]');\" ><img src='../0bmp/pasta.png' align='absmiddle' border='0' width='25' height='25'>Ver </a></td></tr>";
			}	
			
		}
	}
	
	echo $a;

	

//*************************************************
//* mostra a ficha cadastral do motorista
//************************************************
} else if ( $_GET['sq']	== 'mostraficha' ) { 


	$a = '';

	$sqlpes = "			
		select 
			nomepessoa,
			cep,
			cidade,
			endereco,			
			tpessoa.cpfcnpj,
			uf,			
			fone,
			celular,
			email,
			
			rg,
			ufrg,
			ufnascimento,
			cidadenascimento,
			dtnascimento,
			datavalidadecnh,
			ufcnh,
			categoria,
			numregistro,
			nomepai,
			nomemae,
			
			tvalidapessoa.codipessoa as pessoack,
			ckdata,					
			ckcheque,
			qtdcheque,
			ckconsultoria,
			ckcnh,
			obs,
			ckreceita,
			risco,
			ck,
			ckfone,
			cktj,
			to_char((current_timestamp - ckdata), 'DD') as pesquisadodiasatraz
		from
			tpessoa,
			tpessoavinculo,
			tpessoafisica LEFT OUTER JOIN tvalidapessoa ON (tpessoafisica.codipessoa = tvalidapessoa.codipessoa)
			
		where tpessoa.codipessoa = $_GET[codipessoa] and
			tpessoa.codipessoa = tpessoafisica.codipessoa and
			tpessoa.codipessoa = tpessoavinculo.codipessoa and
			tpessoavinculo.contaprincipal = $_GET[contaprincipal] ";
	
		
	$respesposta = pg_exec($sqlpes);			
	$arrpes = pg_fetch_array($respesposta,0,PGSQL_ASSOC);
	

	// essa parte mostra os dados do motorista
	
	$a .="<table class='tabla_cabecera' border='0' align=center width='100%' >";
		$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=4><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'> </td></tr>";
		$a .="<tr><td> Cpf  </td><td> $arrpes[cpfcnpj]</td>";
		$a .="	<td> Nome </td><td> $arrpes[nomepessoa] </td></tr>";
		$a .="<tr><td> End. </td><td> $arrpes[endereco]</td>";
		$a .="	<td> Cidade </td><td> $arrpes[cidade] - $arrpes[uf]</td>";				
		$a .="<tr><td> Fone </td><td> $arrpes[fone]</td>";     
		$a .="	<td> Celular </td><td> $arrpes[celular] </td>	</tr>";   
		$a .="<tr><td> RG </td><td> $arrpes[rg] - $arrpes[ufrg]  </td>";
		$a .="    <td> Nasc </td><td> $arrpes[dtnascimento] $arrpes[cidadenascimento] - $arrpes[ufnascimento] </td></tr>";
		$a .="<tr><td> Pai </td><td> $arrpes[nomepai]</td>";
		$a .="	<td> Mae </td><td> $arrpes[nomemae]</td></tr>";
		$a .="<tr><td> CNH </td><td> $arrpes[numregistro] - $arrpes[ufcnh]</td>";
		$a .="	<td> Val.CNH </td><td> $arrpes[datavalidadecnh] - CAT $arrpes[categoria] </td></tr>";

	$a .="</table><table align=center width='100%' >";

	
		//***********************************************
		// verifica se tem avisos para o motorista
		//***********************************************
		$sqlpesocorrencia = "
			select obs,
				to_char(datacriacao, 'DD/MM/YY') as datacriacao,
				usuario
			from tocorrencia
			where trim(chavedebusca) = '$_GET[codipessoa]'
			order by codiocorrencia desc";
		
		//$a .="$sqlpesocorrencia";
		
		$ressqlopescorrencia = pg_exec($sqlpesocorrencia);	
		
		if ( pg_numrows($ressqlopescorrencia) > 0 ){
		
			$avisos = "ATENCAO ! Este motorista possui avisos nos sitema \n";
			
			for ($i=0; $i < pg_numrows($ressqlopescorrencia ); $i++) {

				$arrpesocorrencia = pg_fetch_array($ressqlopescorrencia,$i,PGSQL_ASSOC);
					
				$avisos .= "$arrpesocorrencia[datacriacao] $arrpesocorrencia[usuario] - $arrpesocorrencia[obs] \n";
			}
		}
		
		
		
		//*************************
		//* cria a tela de consulta 
		//*************************
		
		//status geral
		$a .="<tr class='botonmoduleon'>
				<td colspan=4><textarea readonly COLS=88 ROWS=1>$arrpes[obs]</textarea>
					<textarea readonly COLS=88 ROWS=1>$avisos</textarea> 
					<a href='../0ocorrencia/criaocorrencia.php?chavedebusca=$_GET[codipessoa]&criterioporget=CODIPESSOA' > <img src='../0bmp/atualiza.png' width='25' height='25'  border='0' align='absmiddle'> Cria Aviso </a> </td></tr>";			

	$a .="</table>";


	//***********************************************************
	//* mostra as referencias
	//***********************************************************
						
	$sqlref = "
		select
			nome,
			fone,
			cidade,
			uf,
			contato,
			ckdata,
			obs,
			ck,
			treferencia.codireferencia as codireferencia,
			to_char((current_timestamp - ckdata), 'DD') as pesquisadodiasatraz
		from treferencia,
			treferenciapessoa
		where codipessoa = '$_GET[codipessoa]' and
			treferencia.codireferencia = treferenciapessoa.codireferencia
			order by codireferencia desc ";		
		
	$respref = pg_exec($sqlref);
	
	if ( pg_numrows($respref) > 0 ){

		$a .="<table class='tabla_cabecera' border='0' align=center width='100%'>";
		$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=4><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'> Referencias </td></tr>";
			for ($i=0; $i < pg_numrows($respref ); $i++) {

				$arrref=pg_fetch_array($respref,$i,PGSQL_ASSOC);

				$a .="<tr><td>$arrref[fone] </td>";
				$a .="	<td>$arrref[nome] </td>	";
				$a .="  <td>$arrref[contato] </td>	";
				$a .="	<td>$arrref[cidade] - $arrref[uf]</td>";
				
				$a .="<tr class='botonmoduleon'><td colspan=4> <textarea readonly COLS=87 ROWS=1> $arrref[obs] </textarea> </td></tr>";
			}
		$a .="</table>";

	}			

	//****************************************
	//* VEICULO
	//****************************************
	if (strlen($_GET['placacarro']) > 0 ) {
		
		$sqlcarro = "			
			select 
				tcarro.placa as placa,
				tcarro.codipessoa as codipessoa,
				ufplaca,
				renavan,
				chassi,
				anofabricacao,
				cor,
				categoria,
				marca,
				modelo,
				nomepessoa,
				cep,
				cidade,
				uf,
				endereco,					
				fone,
				celular,
				tipopessoa,
				cpfcnpj,
				tcarro.numeroseguranca,					
				to_char((current_timestamp - ckdata), 'DD') as pesquisadodiasatraz,
				ckdata,
				cklicenciamento,	
				ckantt,				
				tvalidaplaca.placa as validaplaca_placa,
				ckpropriedade,
				obs,
				ckcheque,
				ckfone,
				ckreceita,
				ck
			from
				tpessoa,
				tcarro LEFT OUTER JOIN tvalidaplaca ON (tcarro.placa = tvalidaplaca.placa)
			where tcarro.placa = '$_GET[placacarro]'  and
				tcarro.codipessoa = tpessoa.codipessoa ";

		$rescarro = pg_exec($sqlcarro);
					
		if ( pg_numrows($rescarro) > 0 ){

			$arrcarro = pg_fetch_array($rescarro,0,PGSQL_ASSOC);
			
			// aqui eu verifico se tem algum registro em tvalidaplaca
			// se nao tiver eu faco um insert senao vai dar ploblema nos updates abaixo
						
			$a .="<table class='tabla_cabecera' border='0'  align=center width='100%' >";
				$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=6><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'>Cavalo/truck/utilitario </td></tr>";
				$a .="<tr><td> Placa  </td><td> $arrcarro[placa]  UF $arrcarro[ufplaca]</td>";
				$a .="	<td> Categoria  </td><td> $arrcarro[categoria] </td>";
				$a .="<tr><td> Marca </td><td>$arrcarro[marca]</td>";
				$a .="	<td> Modelo </td><td>$arrcarro[modelo]</td></tr>		 ";
				$a .="<tr><td> Chassi </td><td>$arrcarro[chassi]</td>";
				$a .="	<td> Renavan </td><td>$arrcarro[renavan]</td></tr>";
				$a .="<tr><td> Ano Fabr. </td><td>$arrcarro[anofabricacao]</td>		 ";
				$a .="	<td> Cor </td><td> $arrcarro[cor]</td></tr>";
				
//			$a .="</table><table align=center width='100%' >";
				

				
				//******************************************
				// verifica se tem ocorrencia para o veiculo
				//******************************************
				$sqlpesocorrencia = "
					select obs,
						to_char(datacriacao, 'DD/MM/YY') as datacriacao,
						usuario
					from tocorrencia
					where trim(chavedebusca) = '$arrcarro[placa]'	
					order by codiocorrencia ";
			
				$ressqlopescorrencia = pg_exec($sqlpesocorrencia);	
				
				if ( pg_numrows($ressqlopescorrencia) > 0 ){
				
					$avisos = "ATENCAO ! Este veiculo possui avisos no sitema \n";
					
					for ($i=0; $i < pg_numrows($ressqlopescorrencia ); $i++) {

						$arrpesocorrencia = pg_fetch_array($ressqlopescorrencia,$i,PGSQL_ASSOC);
					
						//$a .="<table class='tabla_cabecera' border='0'  align=center width='100%' >";
						
						$avisos .= "$arrpesocorrencia[datacriacao] $arrpesocorrencia[usuario] -  $arrpesocorrencia[obs] \n";
					}
				}

				//*****************************
				//* tela de consulta de veiculo
				//*****************************
				
				$a.="<tr class='botonmoduleon'><td colspan=4><textarea readonly COLS=90 ROWS=1> $arrcarro[obs] </textarea>   
						<a href='../0ocorrencia/criaocorrencia.php?chavedebusca=$_GET[placacarro]&criterioporget=PLACA' > <img src='../0bmp/atualiza.png' width='25' height='25'  border='0' align='absmiddle'> Cria Aviso </a>";			
		
			$a .="</table>";	
		}
	}

	//****************************************
	//* placareboque
	//****************************************
	if (strlen($_GET['placareboque']) > 0 ) {

		// zera a variavel de avisos senao vai mostrar o mesmo do motorista
		$avisos = '';	

		$sqlplacareboque = "			
			select 
				tcarro.placa as placa,
				tcarro.codipessoa as codipessoa,
				ufplaca,
				renavan,
				chassi,
				anofabricacao,
				cor,
				categoria,
				marca,
				modelo,
				nomepessoa,
				cep,
				antt,
				cidade,
				uf,
				endereco,					
				fone,
				celular,
				tipopessoa,
				cpfcnpj,
				tcarro.numeroseguranca,					
				to_char((current_timestamp - ckdata), 'DD') as pesquisadodiasatraz,
				ckdata,
				cklicenciamento,		
				ckantt,				
				tvalidaplaca.placa as validaplaca_placa,
				ckpropriedade,
				obs,
				ckcheque,
				ckfone,
				ckreceita,
				ck
			from
				tpessoa,
				tcarro LEFT OUTER JOIN tvalidaplaca ON (tcarro.placa = tvalidaplaca.placa)
			where tcarro.placa = '$_GET[placareboque]'  and
				tcarro.codipessoa = tpessoa.codipessoa ";

		$resplacareboque = pg_exec($sqlplacareboque);
					
		if ( pg_numrows($resplacareboque) > 0 ){

			$arrplacareboque = pg_fetch_array($resplacareboque,0,PGSQL_ASSOC);
			
			// aqui eu verifico se tem algum registro em tvalidaplaca
			// se nao tiver eu faco um insert senao vai dar ploblema nos updates abaixo
			
			
			
			$a .="<BR><table class='tabla_cabecera' border='0'  align=center width='100%' >";
				$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=4><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'>Reboque </td></tr>";


				$a .="<tr><td> Cpf/Cnpj: </td><td> $arrplacareboque[cpfcnpj]</td>";
				$a .="	<td> Nome: </td><td> $arrplacareboque[nomepessoa] </td></tr>";
				$a .="<tr><td> Endereco: </td><td> $arrplacareboque[endereco]</td>";
				$a .="	<td> Cidade: </td><td> $arrplacareboque[cidade] - $arrplacareboque[uf]</td>";				
				$a .="<tr><td> Fone: </td><td> $arrplacareboque[fone]</td>";     
				$a .="	<td> Celular: </td><td> $arrplacareboque[celular] N.o Seguranca: $arrplacareboque[numeroseguranca]</td>	</tr>";   

				$a .="<tr><td> Placa:  </td><td> $arrplacareboque[placa]  UF: $arrplacareboque[ufplaca]</td>";
				$a .="	<td> Categoria:  </td><td> $arrplacareboque[categoria] </td>";
				$a .="<tr><td> Marca: </td><td>$arrplacareboque[marca]</td>";
				$a .="	<td> Modelo: </td><td>$arrplacareboque[modelo]</td></tr>		 ";
				$a .="<tr><td> Chassi: </td><td>$arrplacareboque[chassi]</td>";
				$a .="	<td> Renavan: </td><td>$arrplacareboque[renavan] - ANTT: $arrplacareboque[antt] </td></tr>";
				$a .="<tr><td> Ano Fabr: </td><td>$arrplacareboque[anofabricacao]</td>		 ";
				$a .="	<td> Cor: </td><td> $arrplacareboque[cor]</td></tr>";
				
//				$a .="</table><table width='100%' >";
				
				//******************************************
				// verifica se tem ocorrencia para o reboque
				//******************************************
				$sqlpesocorrencia = "
					select obs,
						to_char(datacriacao, 'DD/MM/YY') as datacriacao,
						usuario
					from tocorrencia
					where trim(chavedebusca) = '$arrplacareboque[placa]'	
					order by codiocorrencia ";
			
				$ressqlopescorrencia = pg_exec($sqlpesocorrencia);	
				
				if ( pg_numrows($ressqlopescorrencia) > 0 ){
				
					$avisos = "<br><img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle'> ATENCAO ! Este reboque possui avisos no sitema \n";
					$avisos = "<textarea readonly COLS=90 ROWS=1>";
					
					for ($i=0; $i < pg_numrows($ressqlopescorrencia ); $i++) {

						$arrpesocorrencia = pg_fetch_array($ressqlopescorrencia,$i,PGSQL_ASSOC);
					
						//$a .="<table class='tabla_cabecera' border='0'  align=center width='100%' >";
						
						$avisos .= "$arrpesocorrencia[datacriacao] $arrpesocorrencia[usuario] -  $arrpesocorrencia[obs] \n";
					}
					
					$avisos = "</textarea>";
				}

			$a .="</table>";	

		}
	}

	//****************************************
	//* placasemireboque
	//****************************************
	if (strlen($_GET['placasemireboque']) > 0 ) {
		
		$sqlplacasemireboque = "			
			select 
				tcarro.placa as placa,
				tcarro.codipessoa as codipessoa,
				ufplaca,
				renavan,
				chassi,
				anofabricacao,
				cor,
				categoria,
				marca,
				modelo,
				antt,
				nomepessoa,
				cep,
				cidade,
				uf,
				endereco,					
				fone,
				celular,
				tipopessoa,
				cpfcnpj,
				tcarro.numeroseguranca,					
				to_char((current_timestamp - ckdata), 'DD') as pesquisadodiasatraz,
				ckdata,
				cklicenciamento,	
				ckantt,				
				tvalidaplaca.placa as validaplaca_placa,
				ckpropriedade,
				obs,
				ckcheque,
				ckfone,
				ckreceita,
				ck
			from
				tpessoa,
				tcarro LEFT OUTER JOIN tvalidaplaca ON (tcarro.placa = tvalidaplaca.placa)
			where tcarro.placa = '$_GET[placasemireboque]'  and
				tcarro.codipessoa = tpessoa.codipessoa ";

		$resplacasemireboque = pg_exec($sqlplacasemireboque);
					
		if ( pg_numrows($resplacasemireboque) > 0 ){

			$arrplacasemireboque = pg_fetch_array($resplacasemireboque,0,PGSQL_ASSOC);
			
			// aqui eu verifico se tem algum registro em tvalidaplaca
			// se nao tiver eu faco um insert senao vai dar ploblema nos updates abaixo
			
			
			
			$a .="<BR><table class='tabla_cabecera' border='0'  align=center width='100%' >";
				$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=6><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'>SemiReboque </td></tr>";

				$a .="<tr><td> Cpf/Cnpj: </td><td> $arrplacasemireboque[cpfcnpj]</td>";
				$a .="	<td> Nome: </td><td> $arrplacasemireboque[nomepessoa] </td></tr>";
				$a .="<tr><td> Endereco: </td><td> $arrplacasemireboque[endereco]</td>";
				$a .="	<td> Cidade: </td><td> $arrplacasemireboque[cidade] - $arrplacasemireboque[uf]</td>";				
				$a .="<tr><td> Fone: </td><td> $arrplacasemireboque[fone]</td>";     
				$a .="	<td> Celular: </td><td> $arrplacasemireboque[celular] N.o Seguranca: $arrplacasemireboque[numeroseguranca]</td>	</tr>";   


				$a .="<tr><td> Placa  </td><td> $arrplacasemireboque[placa]  UF $arrplacasemireboque[ufplaca]</td>";
				$a .="	<td> Categoria  </td><td> $arrplacasemireboque[categoria] </td>";
				$a .="<tr><td> Marca </td><td>$arrplacasemireboque[marca]</td>";
				$a .="	<td> Modelo </td><td>$arrplacasemireboque[modelo]</td></tr>		 ";
				$a .="<tr><td> Chassi </td><td>$arrplacasemireboque[chassi]</td>";
				$a .="	<td> Renavan </td><td>$arrplacasemireboque[renavan] - ANTT $arrplacasemireboque[antt]</td></tr>";
				$a .="<tr><td> Ano Fabr. </td><td>$arrplacasemireboque[anofabricacao]</td>		 ";
				$a .="	<td> Cor </td><td> $arrplacasemireboque[cor]</td></tr>";
				
//				$a .="</table><table align=center width='100%' >";
				
				//******************************************
				// verifica se tem ocorrencia para o semi reboque
				//******************************************
				$sqlpesocorrencia = "
					select obs,
						to_char(datacriacao, 'DD/MM/YY') as datacriacao,
						usuario
					from tocorrencia
					where trim(chavedebusca) = '$arrplacasemireboque[placa]'	
					order by codiocorrencia ";
			
				$ressqlopescorrencia = pg_exec($sqlpesocorrencia);	
				
				if ( pg_numrows($ressqlopescorrencia) > 0 ){
				
					$avisos = "<br><img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle'> ATENCAO ! Este Semi-reboque possui avisos no sitema \n";
					$avisos = "<textarea readonly COLS=90 ROWS=1>";
					
					for ($i=0; $i < pg_numrows($ressqlopescorrencia ); $i++) {

						$arrpesocorrencia = pg_fetch_array($ressqlopescorrencia,$i,PGSQL_ASSOC);
					
						//$a .="<table class='tabla_cabecera' border='0'  align=center width='100%' >";
						
						$avisos .= "$arrpesocorrencia[datacriacao] $arrpesocorrencia[usuario] -  $arrpesocorrencia[obs] \n";
					}
					
					$avisos = "</textarea>";
				}

				
			$a .="</table>	";
				
				
		}
	}		

	
	
	//****************************************
	//* placaterceiroreboque
	//****************************************
	if (strlen($_GET['placaterceiroreboque']) > 0 ) {
		
		$sqlplacaterceiroreboque = "			
			select 
				tcarro.placa as placa,
				tcarro.codipessoa as codipessoa,
				ufplaca,
				renavan,
				chassi,
				anofabricacao,
				cor,
				categoria,
				marca,
				modelo,
				antt,
				nomepessoa,
				cep,
				cidade,
				uf,
				endereco,					
				fone,
				celular,
				tipopessoa,
				cpfcnpj,
				tcarro.numeroseguranca,					
				to_char((current_timestamp - ckdata), 'DD') as pesquisadodiasatraz,
				ckdata,
				cklicenciamento,	
				ckantt,				
				tvalidaplaca.placa as validaplaca_placa,
				ckpropriedade,
				obs,
				ckcheque,
				ckfone,
				ckreceita,
				ck
			from
				tpessoa,
				tcarro LEFT OUTER JOIN tvalidaplaca ON (tcarro.placa = tvalidaplaca.placa)
			where tcarro.placa = '$_GET[placaterceiroreboque]'  and
				tcarro.codipessoa = tpessoa.codipessoa ";

		$resplacaterceiroreboque = pg_exec($sqlplacaterceiroreboque);
					
		if ( pg_numrows($resplacaterceiroreboque) > 0 ){

			$arrplacaterceiroreboque = pg_fetch_array($resplacaterceiroreboque,0,PGSQL_ASSOC);
			
			// aqui eu verifico se tem algum registro em tvalidaplaca
			// se nao tiver eu faco um insert senao vai dar ploblema nos updates abaixo
			
			
			
			$a .="<BR><table class='tabla_cabecera' border='0'  align=center width='100%' >";
				$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=6><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'>terceiroReboque </td></tr>";

				$a .="<tr><td> Cpf/Cnpj: </td><td> $arrplacaterceiroreboque[cpfcnpj]</td>";
				$a .="	<td> Nome: </td><td> $arrplacaterceiroreboque[nomepessoa] </td></tr>";
				$a .="<tr><td> Endereco: </td><td> $arrplacaterceiroreboque[endereco]</td>";
				$a .="	<td> Cidade: </td><td> $arrplacaterceiroreboque[cidade] - $arrplacaterceiroreboque[uf]</td>";				
				$a .="<tr><td> Fone: </td><td> $arrplacaterceiroreboque[fone]</td>";     
				$a .="	<td> Celular: </td><td> $arrplacaterceiroreboque[celular] N.o Seguranca: $arrplacaterceiroreboque[numeroseguranca]</td>	</tr>";   


				$a .="<tr><td> Placa  </td><td> $arrplacaterceiroreboque[placa]  UF $arrplacaterceiroreboque[ufplaca]</td>";
				$a .="	<td> Categoria  </td><td> $arrplacaterceiroreboque[categoria] </td>";
				$a .="<tr><td> Marca </td><td>$arrplacaterceiroreboque[marca]</td>";
				$a .="	<td> Modelo </td><td>$arrplacaterceiroreboque[modelo]</td></tr>		 ";
				$a .="<tr><td> Chassi </td><td>$arrplacaterceiroreboque[chassi]</td>";
				$a .="	<td> Renavan </td><td>$arrplacaterceiroreboque[renavan] - ANTT $arrplacaterceiroreboque[antt]</td></tr>";
				$a .="<tr><td> Ano Fabr. </td><td>$arrplacaterceiroreboque[anofabricacao]</td>		 ";
				$a .="	<td> Cor </td><td> $arrplacaterceiroreboque[cor]</td></tr>";
				
//				$a .="</table><table align=center width='100%' >";
				
				//******************************************
				// verifica se tem ocorrencia para o terceiro reboque
				//******************************************
				$sqlpesocorrencia = "
					select obs,
						to_char(datacriacao, 'DD/MM/YY') as datacriacao,
						usuario
					from tocorrencia
					where trim(chavedebusca) = '$arrplacaterceiroreboque[placa]'	
					order by codiocorrencia ";
			
				$ressqlopescorrencia = pg_exec($sqlpesocorrencia);	
				
				if ( pg_numrows($ressqlopescorrencia) > 0 ){
				
					$avisos = "<br><img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle'> ATENCAO ! Este terceiro-reboque possui avisos no sitema \n";
					$avisos = "<textarea readonly COLS=90 ROWS=1>";
					
					for ($i=0; $i < pg_numrows($ressqlopescorrencia ); $i++) {

						$arrpesocorrencia = pg_fetch_array($ressqlopescorrencia,$i,PGSQL_ASSOC);
					
						//$a .="<table class='tabla_cabecera' border='0'  align=center width='100%' >";
						
						$avisos .= "$arrpesocorrencia[datacriacao] $arrpesocorrencia[usuario] -  $arrpesocorrencia[obs] \n";
					}
					
					$avisos = "</textarea>";
				}

				
			$a .="</table>	";
				
				
		}
	}
	
  echo "$a"; 

	
//***************************************************************
//* mostra a ficha cadastral do motorista solicitado pelo cliente
//***************************************************************
} else if ( $_GET['sq']	== 'mostraficha_aocliente' ) { 

	$a = '';
	
	if ($_GET['codipessoa'] != '') {
	
		$sqlpes = "			
			select 
				tpessoa.nomepessoa,
				tpessoa.cep,
				tpessoa.cidade,
				tpessoa.endereco,			
				tpessoa.cpfcnpj,
				tpessoa.uf,			
				tpessoa.fone,
				tpessoa.celular,
				tpessoa.email,
				
				tpessoafisica.rg,
				tpessoafisica.ufrg,
				tpessoafisica.ufnascimento,
				tpessoafisica.cidadenascimento,
				tpessoafisica.dtnascimento,
				tpessoafisica.datavalidadecnh,
				tpessoafisica.ufcnh,
				tpessoafisica.categoria,
				tpessoafisica.numregistro,
				tpessoafisica.nomepai,
				tpessoafisica.nomemae	
				
			from
				tpessoa,
				tpessoafisica,
				tpessoavinculo
				
			where tpessoa.codipessoa = $_GET[codipessoa] and
				tpessoa.codipessoa = tpessoafisica.codipessoa  and
				tpessoa.codipessoa = tpessoavinculo.codipessoa ";	
			
		//verifico se a conta principal � century, se sim pode liberar a pesquisa
		// se nao, preciso restringir pela conta principal acesso somente para as 
		// filiais, para que nao fique acesso liberado para todo mundo visualizar a ficha.	
		
		if ($_SESSION['contaprincipal'] != 48813) {
		
			$sqlpes.= " and tpessoavinculo.contaprincipal = $_SESSION[contaprincipal] ";
			// puxo pela tpessoavinculo para nao precisar acessar tchamada
		}
			
		$respesposta = pg_exec($sqlpes);	

		if ( pg_numrows($respesposta) > 0 ){
						
			$arrpes = pg_fetch_array($respesposta,0,PGSQL_ASSOC);
							
			//----------------------------------
			// servis
			//----------------------------------
			if ( $_SESSION[contaprincipal] == 855705){
				$logo ="<img src='../0layout/elastix/logoservis.png' border='0'  width='100' height='40'>";
				  
			//----------------------------------
			// cci
			//----------------------------------
			} else if ( $_SESSION[contaprincipal] == 908311) {
				$logo ="<img src='../0layout/elastix/logo-cci.gif' border='0'  width='100' height='40'>";
				  
			//----------------------------------
			// lideransat
			//----------------------------------
			} else if ( $_SESSION[contaprincipal] == 871563) {
				$logo ="<img src='../0layout/elastix/logo-lideransat.png' border='0'  width='100' height='40'>";
				
			//----------------------------------
			// Krona
			//----------------------------------
			}else if ( $_SESSION[contaprincipal] == 920636){
				$logo ="<img src='../0layout/elastix/logokrona.jpg' border='0'  width='100' height='40'>";
				
			//----------------------------------
			// Logirisco
			//----------------------------------
			} else if ( $_SESSION[contaprincipal] == 920633) {
				$logo ="<img src='../0layout/elastix/logo-logirisco.png' border='0'  width='100' height='40'>";
				
			//----------------------------------
			// Century
			//----------------------------------
			} else {
				$logo ="<img src='../0layout/elastix/logo-century.gif' border='0'  width='100' height='40'>";

			}			
			$a .="<table border='0' align=center width='100%' >";
			$a .="<tr><td colspan='3' width='100%'><hr color='#000000' size='2' width='100%'></TD></tr>";
			$a .="<tr><td>$logo </td><td valign='middle'><h2>Ficha Cadastral</td></tr>";


			// essa parte mostra os dados do motorista
			$a .="<table border='0' align=center width='100%' >";
			$a .="<tr><td  valign='middle' colspan=4><hr noshade></td></tr>";
			$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=4><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'> Motorista </td></tr>";
			
			$a .="<table class='tabla_cabecera' border='0' align=center width='100%' >";
			$a .="<tr><td> Cpf:  </td><td> $arrpes[cpfcnpj]</td>";
			$a .="	<td> Nome: </td><td> <b>$arrpes[nomepessoa] </td></tr>";
			$a .="<tr><td> End.: </td><td> $arrpes[endereco]</td>";
			$a .="	<td> Cidade: </td><td> $arrpes[cidade] - $arrpes[uf]</td>";				
			$a .="<tr><td> Fone: </td><td> $arrpes[fone]</td>";     
			$a .="	<td> Celular: </td><td> $arrpes[celular] </td>	</tr>";   
			$a .="<tr><td> RG: </td><td> $arrpes[rg] - $arrpes[ufrg]  </td>";
			$a .="    <td> Nasc: </td><td> $arrpes[dtnascimento] $arrpes[cidadenascimento] - $arrpes[ufnascimento] </td></tr>";
			$a .="<tr><td> Pai: </td><td> $arrpes[nomepai]</td>";
			$a .="	<td> Mae: </td><td> $arrpes[nomemae]</td></tr>";
			$a .="<tr><td> CNH: </td><td> $arrpes[numregistro] - $arrpes[ufcnh] </td>";
			$a .="	<td> Val.CNH: </td><td> $arrpes[datavalidadecnh] - CAT $arrpes[categoria] </td></tr>";

			$a .="</table><table align=center width='100%' >";



			//***********************************************************
			//* mostra as referencias
			//***********************************************************
								
			$sqlref = "
				select
					nome,
					fone,
					cidade,
					uf,
					contato,
					ckdata,
					obs,
					ck,
					treferencia.codireferencia as codireferencia,
					to_char((current_timestamp - ckdata), 'DD') as pesquisadodiasatraz
				from treferencia,
					treferenciapessoa
				where codipessoa = '$_GET[codipessoa]' and
					treferencia.codireferencia = treferenciapessoa.codireferencia
					order by codireferencia desc ";		
				
			$respref = pg_exec($sqlref);
			
			if ( pg_numrows($respref) > 0 ){

				$a .="<table border='0' align=center width='100%' >";
				$a .="<tr><td  valign='middle' colspan=4><hr noshade></td></tr>";
				$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=4><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'> Referencias </td></tr>";
				$a .="</table>";
				$a .="<table class='tabla_cabecera' border='0' align=center width='100%'>";
				//$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=4><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'> Referencias </td></tr>";
					for ($i=0; $i < pg_numrows($respref ); $i++) {

						$arrref=pg_fetch_array($respref,$i,PGSQL_ASSOC);

						$a .="<tr><td>$arrref[fone] </td>";
						$a .="	<td>$arrref[nome] </td>	";
						$a .="  <td>$arrref[contato] </td>	";
						$a .="	<td>$arrref[cidade] - $arrref[uf]</td>";
						
						//$a .="<tr class='botonmoduleon'><td colspan=4> <textarea readonly COLS=87 ROWS=1> $arrref[obs] </textarea> </td></tr>";
					}
				$a .="</table>";

			}			
		}else{
			$a .= faviso2("Este registro ( codigo $_GET[codipessoa] ) ainda nao foi pesquisado ","pela matriz e/ou filial");
		}
	}
	
	
	if ($_GET['placacarro'] != '') {	
		
		//****************************************
		//* VEICULO
		//****************************************
		
			
		$sqlcarro = "			
			select 
				tcarro.placa as placa,
				tcarro.codipessoa as codipessoa,
				tcarro.ufplaca,
				tcarro.renavan,
				tcarro.chassi,
				tcarro.anofabricacao,
				tcarro.cor,
				tcarro.categoria,
				tcarro.marca,
				tcarro.modelo,
				tcarro.numeroseguranca,
				tpessoa.nomepessoa,
				tpessoa.cep,
				tpessoa.cidade,
				tpessoa.uf,
				tpessoa.endereco,					
				tpessoa.fone,
				tpessoa.celular,				
				tpessoa.cpfcnpj
			from
				tpessoa,
				tcarro,
				tchamada				
			where tcarro.placa = '$_GET[placacarro]'  and
				tcarro.placa = tchamada.placacarro and
				tcarro.codipessoa = tpessoa.codipessoa ";

				
			
		if ($_SESSION['contaprincipal'] != 48813) {
		
			$sqlcarro.= " and tchamada.contaprincipal = $_SESSION[contaprincipal] ";
			// puxo pela tpessoavinculo para nao precisar acessar tchamada
		}	
				
				
				
				
		$rescarro = pg_exec($sqlcarro);
					
		if ( pg_numrows($rescarro) > 0 ){

			$arrcarro = pg_fetch_array($rescarro,0,PGSQL_ASSOC);
			
			// aqui eu verifico se tem algum registro em tvalidaplaca
			// se nao tiver eu faco um insert senao vai dar ploblema nos updates abaixo
						
			$a .="<table border='0' align=center width='100%' >";
			$a .="<tr><td  valign='middle' colspan=4><hr noshade></td></tr>";
			$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=4><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'> Veiculo  </td></tr>";
			$a .="</table>";
			$a .="<table class='tabla_cabecera' border='0' align=center width='100%'>";

			$a .="<tr><td> Cpf/Cnpj: </td><td> $arrcarro[cpfcnpj]</td>";
			$a .="	<td> Nome: </td><td> $arrcarro[nomepessoa] </td></tr>";
			$a .="<tr><td> Endereco: </td><td> $arrcarro[endereco]</td>";
			$a .="	<td> Cidade: </td><td> $arrcarro[cidade] - $arrcarro[uf]</td>";				
			$a .="<tr><td> Fone: </td><td> $arrcarro[fone]</td>";     
			$a .="	<td> Celular: </td><td> $arrcarro[celular] N.oSeguranca: $arrcarro[numeroseguranca]</td>	</tr>";   

			$a .="<tr><td> Placa  </td><td> $arrcarro[placa]  UF $arrcarro[ufplaca]</td>";
			$a .="	<td> Categoria  </td><td> $arrcarro[categoria] </td>";
			$a .="<tr><td> Marca </td><td>$arrcarro[marca]</td>";
			$a .="	<td> Modelo </td><td>$arrcarro[modelo]</td></tr>		 ";
			$a .="<tr><td> Chassi </td><td>$arrcarro[chassi]</td>";
			$a .="	<td> Renavan </td><td>$arrcarro[renavan]</td></tr>";
			$a .="<tr><td> Ano Fabr. </td><td>$arrcarro[anofabricacao]</td>		 ";
			$a .="	<td> Cor </td><td> $arrcarro[cor]</td></tr>";
	
	
		}else{
		
			$a .= faviso2("Placa $_GET[placacarro], ainda nao foi pesquisado(a) ","pela matriz e/ou filial");
			
		}
	}

	
	if ($_GET['placareboque'] != '') {	
	
		//****************************************
		//* placareboque
		//****************************************

		// zera a variavel de avisos senao vai mostrar o mesmo do motorista
		
		$sqlplacareboque = "			
			select 
				tcarro.placa as placa,
				tcarro.codipessoa as codipessoa,
				tcarro.ufplaca,
				tcarro.renavan,
				tcarro.chassi,
				tcarro.anofabricacao,
				tcarro.cor,
				tcarro.categoria,
				tcarro.marca,
				tcarro.modelo,
				tcarro.numeroseguranca,
				tpessoa.nomepessoa,
				tpessoa.cep,
				tpessoa.cidade,
				tpessoa.uf,
				tpessoa.endereco,					
				tpessoa.fone,
				tpessoa.celular,				
				tpessoa.cpfcnpj
			from
				tpessoa,
				tcarro,
				tchamada				
			where tcarro.placa = '$_GET[placareboque]'  and
				tcarro.placa = tchamada.placareboque and
				tcarro.codipessoa = tpessoa.codipessoa ";

				
			
		if ($_SESSION['contaprincipal'] != 48813) {
		
			$sqlplacareboque.= " and tchamada.contaprincipal = $_SESSION[contaprincipal] ";
			// puxo pela tpessoavinculo para nao precisar acessar tchamada
		}	

		$resplacareboque = pg_exec($sqlplacareboque);
					
		if ( pg_numrows($resplacareboque) > 0 ){

			$arrplacareboque = pg_fetch_array($resplacareboque,0,PGSQL_ASSOC);
			
			// aqui eu verifico se tem algum registro em tvalidaplaca
			// se nao tiver eu faco um insert senao vai dar ploblema nos updates abaixo
			
			
			
			$a .="<table border='0' align=center width='100%' >";
			$a .="<tr><td  valign='middle' colspan=4><hr noshade></td></tr>";
			$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=4><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'> Reboque  </td></tr>";
			$a .="</table>";
			$a .="<table class='tabla_cabecera' border='0' align=center width='100%'>";


				$a .="<tr><td> Cpf/Cnpj: </td><td> $arrplacareboque[cpfcnpj]</td>";
				$a .="	<td> Nome: </td><td> $arrplacareboque[nomepessoa] </td></tr>";
				$a .="<tr><td> Endereco: </td><td> $arrplacareboque[endereco]</td>";
				$a .="	<td> Cidade: </td><td> $arrplacareboque[cidade] - $arrplacareboque[uf]</td>";				
				$a .="<tr><td> Fone: </td><td> $arrplacareboque[fone]</td>";     
				$a .="	<td> Celular: </td><td> $arrplacareboque[celular] N.o Seguranca: $arrplacareboque[numeroseguranca]</td>	</tr>";   

				$a .="<tr><td> Placa:  </td><td> $arrplacareboque[placa]  UF: $arrplacareboque[ufplaca]</td>";
				$a .="	<td> Categoria:  </td><td> $arrplacareboque[categoria] </td>";
				$a .="<tr><td> Marca: </td><td>$arrplacareboque[marca]</td>";
				$a .="	<td> Modelo: </td><td>$arrplacareboque[modelo]</td></tr>		 ";
				$a .="<tr><td> Chassi: </td><td>$arrplacareboque[chassi]</td>";
				$a .="	<td> Renavan: </td><td>$arrplacareboque[renavan] - ANTT: $arrplacareboque[antt] </td></tr>";
				$a .="<tr><td> Ano Fabr: </td><td>$arrplacareboque[anofabricacao]</td>		 ";
				$a .="	<td> Cor: </td><td> $arrplacareboque[cor]</td></tr>";
				

		}else{
			$a .= faviso2("Placa reboque $_GET[placareboque], ainda nao foi pesquisado(a) ","pela matriz e/ou filial ");
		}
			
	}

	
	
	if ($_GET['placasemireboque'] != '') {	
	
		//****************************************
		//* placasemireboque
		//****************************************
			
		$sqlplacasemireboque = "			
			select 
				tcarro.placa as placa,
				tcarro.codipessoa as codipessoa,
				tcarro.ufplaca,
				tcarro.renavan,
				tcarro.chassi,
				tcarro.anofabricacao,
				tcarro.cor,
				tcarro.categoria,
				tcarro.marca,
				tcarro.modelo,
				tcarro.numeroseguranca,
				tpessoa.nomepessoa,
				tpessoa.cep,
				tpessoa.cidade,
				tpessoa.uf,
				tpessoa.endereco,					
				tpessoa.fone,
				tpessoa.celular,				
				tpessoa.cpfcnpj
			from
				tpessoa,
				tcarro,
				tchamada				
			where tcarro.placa = '$_GET[placasemireboque]'  and
				tcarro.placa = tchamada.placasemireboque and
				tcarro.codipessoa = tpessoa.codipessoa ";

				
			
		if ($_SESSION['contaprincipal'] != 48813) {
		
			$sqlplacasemireboque.= " and tchamada.contaprincipal = $_SESSION[contaprincipal] ";
			// puxo pela tpessoavinculo para nao precisar acessar tchamada
		}	

		$resplacasemireboque = pg_exec($sqlplacasemireboque);
					
		if ( pg_numrows($resplacasemireboque) > 0 ){

			$arrplacasemireboque = pg_fetch_array($resplacasemireboque,0,PGSQL_ASSOC);
			
			// aqui eu verifico se tem algum registro em tvalidaplaca
			// se nao tiver eu faco um insert senao vai dar ploblema nos updates abaixo
			
			$a .="<table border='0' align=center width='100%' >";
			$a .="<tr><td  valign='middle' colspan=4><hr noshade></td></tr>";
			$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=4><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'> Semi-Reboque  </td></tr>";
			$a .="</table>";
			$a .="<table class='tabla_cabecera' border='0' align=center width='100%'>";

				$a .="<tr><td> Cpf/Cnpj: </td><td> $arrplacasemireboque[cpfcnpj]</td>";
				$a .="	<td> Nome: </td><td> $arrplacasemireboque[nomepessoa] </td></tr>";
				$a .="<tr><td> Endereco: </td><td> $arrplacasemireboque[endereco]</td>";
				$a .="	<td> Cidade: </td><td> $arrplacasemireboque[cidade] - $arrplacasemireboque[uf]</td>";				
				$a .="<tr><td> Fone: </td><td> $arrplacasemireboque[fone]</td>";     
				$a .="	<td> Celular: </td><td> $arrplacasemireboque[celular] N.oSeguranca: $arrplacasemireboque[numeroseguranca]</td>	</tr>";   

				$a .="<tr><td> Placa  </td><td> $arrplacasemireboque[placa]  UF $arrplacasemireboque[ufplaca]</td>";
				$a .="	<td> Categoria  </td><td> $arrplacasemireboque[categoria] </td>";
				$a .="<tr><td> Marca </td><td>$arrplacasemireboque[marca]</td>";
				$a .="	<td> Modelo </td><td>$arrplacasemireboque[modelo]</td></tr>		 ";
				$a .="<tr><td> Chassi </td><td>$arrplacasemireboque[chassi]</td>";
				$a .="	<td> Renavan </td><td>$arrplacasemireboque[renavan] - ANTT $arrplacasemireboque[antt]</td></tr>";
				$a .="<tr><td> Ano Fabr. </td><td>$arrplacasemireboque[anofabricacao]</td>		 ";
				$a .="	<td> Cor </td><td> $arrplacasemireboque[cor]</td></tr>";
				
		}else{
			$a .= faviso2("Placa semi-reboque $_GET[placasemireboque], ainda nao foi pesquisado(a) ","pela matriz e/ou filial");
		}
	}		

	
	if ($_GET['placaterceiroreboque'] != '') {	
	
		//****************************************
		//* placaterceiroreboque
		//****************************************
			
		$sqlplacaterceiroreboque = "			
			select 
				tcarro.placa as placa,
				tcarro.codipessoa as codipessoa,
				tcarro.ufplaca,
				tcarro.renavan,
				tcarro.chassi,
				tcarro.anofabricacao,
				tcarro.cor,
				tcarro.categoria,
				tcarro.marca,
				tcarro.modelo,
				tcarro.numeroseguranca,
				tpessoa.nomepessoa,
				tpessoa.cep,
				tpessoa.cidade,
				tpessoa.uf,
				tpessoa.endereco,					
				tpessoa.fone,
				tpessoa.celular,				
				tpessoa.cpfcnpj
			from
				tpessoa,
				tcarro,
				tchamada				
			where tcarro.placa = '$_GET[placaterceiroreboque]'  and
				tcarro.placa = tchamada.placaterceiroreboque and
				tcarro.codipessoa = tpessoa.codipessoa ";

				
			
		if ($_SESSION['contaprincipal'] != 48813) {
		
			$sqlplacaterceiroreboque.= " and tchamada.contaprincipal = $_SESSION[contaprincipal] ";
			// puxo pela tpessoavinculo para nao precisar acessar tchamada
		}	

		$resplacaterceiroreboque = pg_exec($sqlplacaterceiroreboque);
					
		if ( pg_numrows($resplacaterceiroreboque) > 0 ){

			$arrplacaterceiroreboque = pg_fetch_array($resplacaterceiroreboque,0,PGSQL_ASSOC);
			
			// aqui eu verifico se tem algum registro em tvalidaplaca
			// se nao tiver eu faco um insert senao vai dar ploblema nos updates abaixo
			
			$a .="<table border='0' align=center width='100%' >";
			$a .="<tr><td  valign='middle' colspan=4><hr noshade></td></tr>";
			$a .="<tr class='moduleTitle'><td class='moduleTitle' valign='middle' colspan=4><img src='../0layout/elastix/1x1.gif' align='absmiddle' border='0'> terceiro-Reboque  </td></tr>";
			$a .="</table>";
			$a .="<table class='tabla_cabecera' border='0' align=center width='100%'>";

				$a .="<tr><td> Cpf/Cnpj: </td><td> $arrplacaterceiroreboque[cpfcnpj]</td>";
				$a .="	<td> Nome: </td><td> $arrplacaterceiroreboque[nomepessoa] </td></tr>";
				$a .="<tr><td> Endereco: </td><td> $arrplacaterceiroreboque[endereco]</td>";
				$a .="	<td> Cidade: </td><td> $arrplacaterceiroreboque[cidade] - $arrplacaterceiroreboque[uf]</td>";				
				$a .="<tr><td> Fone: </td><td> $arrplacaterceiroreboque[fone]</td>";     
				$a .="	<td> Celular: </td><td> $arrplacaterceiroreboque[celular] N.oSeguranca: $arrplacaterceiroreboque[numeroseguranca]</td>	</tr>";   

				$a .="<tr><td> Placa  </td><td> $arrplacaterceiroreboque[placa]  UF $arrplacaterceiroreboque[ufplaca]</td>";
				$a .="	<td> Categoria  </td><td> $arrplacaterceiroreboque[categoria] </td>";
				$a .="<tr><td> Marca </td><td>$arrplacaterceiroreboque[marca]</td>";
				$a .="	<td> Modelo </td><td>$arrplacaterceiroreboque[modelo]</td></tr>		 ";
				$a .="<tr><td> Chassi </td><td>$arrplacaterceiroreboque[chassi]</td>";
				$a .="	<td> Renavan </td><td>$arrplacaterceiroreboque[renavan] - ANTT $arrplacaterceiroreboque[antt]</td></tr>";
				$a .="<tr><td> Ano Fabr. </td><td>$arrplacaterceiroreboque[anofabricacao]</td>		 ";
				$a .="	<td> Cor </td><td> $arrplacaterceiroreboque[cor]</td></tr>";
				
		}else{
			$a .= faviso2("Placa terceiro-reboque $_GET[placaterceiroreboque], ainda nao foi pesquisado(a) ","pela matriz e/ou filial");
		}
	}
	
	
	
	echo "$a"; 

 
   
// salva o grid  	
} else if ( $_GET['sq']	== 'gravatotalserasa' ) {

	$_GET['valorserasa'] = str_replace('.','',$_GET['valorserasa']);
	$_GET['valorserasa'] = (int)$_GET['valorserasa'];
	$msgtvalidapessoa ="";
	$msgtchamada ="";
    
    
	//*******************************
	//	comparo o serasa
	//*******************************
	//At� R$ 20.000,00 - APTO
	//De R$ 20mil a R$ 40mil - limite de carregamento R$ 250mil 
	//De R$ 40mil acima - limite de carregamento R$ 100mil 

	if ($_GET['valorserasa'] >= 20000 && $_GET['valorserasa'] <= 40000){
        
		$msgtvalidapessoa =" ,obs = (' (".date('dmy')." $_SESSION[usuario]) valor total serasa ($_GET[valorserasa])  Carga ate R$ 250.000,00;' || obs)";		
                
		$msgtchamada =" ,resposta = (' Carga ate R$ 250.000,00;' || resposta) , obsresposta = '*** Carga ate R$ 250.000,00' ";												
        
	}else if ($_GET['valorserasa'] > 40000 ){
        
		$msgtvalidapessoa =" ,obs = (' (".date('dmy')." $_SESSION[usuario]) valor total serasa ($_GET[valorserasa])  Carga ate R$ 100.000,00;' || obs)";												
        
		$msgtchamada =" ,resposta = (' Carga ate R$ 100.000,00;' || resposta)  , obsresposta = '*** Carga ate R$ 100.000,00' ";												
        
	}			
	
	$sql = "
		update  tvalidapessoa
		set totalserasa = '$_GET[valorserasa]'
			$msgtvalidapessoa
		where codipessoa = '$_GET[codipessoa]' ";	
		
	$resp = pg_exec($sql);
	
	$sqlchamada = "
		update  tchamada
		set totalserasa = '$_GET[valorserasa]'
			$msgtchamada
		where protocolo = '$_GET[protocolo]' ";	
		
	$respchamada = pg_exec($sqlchamada);
	
	if ($resp && $respchamada) {
	
		echo "1";  // tudo ok
		
	}else{
		echo "<br>$sql<br>$respchamada<br>";  // erro de gravacao
	}	
	
} else if ( $_GET['sq']	== 'gravatotalpontoscnh' ) {

	$_GET['totalpontoscnh'] = str_replace('.','',$_GET['totalpontoscnh']);
	$_GET['totalpontoscnh'] = (int)$_GET['totalpontoscnh'];
	$msgtvalidapessoa ="";
	$msgtchamada ="";
    
    
	//*******************************
	//	comparo o serasa
	//*******************************
	//At� R$ 20.000,00 - APTO
	//De R$ 20mil a R$ 40mil - limite de carregamento R$ 250mil 
	//De R$ 40mil acima - limite de carregamento R$ 100mil 

	if ($_GET['totalpontoscnh'] >= 14){
        
		$msgtvalidapessoa =" ,obs = (' (".date('dmy')." $_SESSION[usuario]) Motorista com  ($_GET[totalpontoscnh])  pontos na cnh;' || obs)";		
                
		$msgtchamada =" ,resposta = ('Motorista com  ($_GET[totalpontoscnh])  pontos na cnh, recomenda-se cautela na contratacao;' || resposta) , obsresposta = 'Motorista com  ($_GET[totalpontoscnh])  pontos na cnh, recomenda-se cautela na contratacao;' ";												
        
	        
	}			
	
	$sql = "
		update  tvalidapessoa
		set totalpontoscnh = '$_GET[totalpontoscnh]'
			$msgtvalidapessoa
		where codipessoa = '$_GET[codipessoa]' ";	
		
	$resp = pg_exec($sql);
	
	$sqlchamada = "
		update  tchamada
		set totalpontoscnh = '$_GET[totalpontoscnh]'
			$msgtchamada
		where protocolo = '$_GET[protocolo]' ";	
		
	$respchamada = pg_exec($sqlchamada);
	
	if ($resp && $respchamada) {
	
		echo "1";  // tudo ok
		
	}else{
		echo "<br>$sql<br>$sqlchamada<br>";  // erro de gravacao
	}		
	
	
// vincula um pesquisador
} else if ( $_GET['sq']	== 'pesquisador' ) {

   vinculapesquisador($_GET['protocolo']);
   
   
// salva o grid  	
} else if ( $_GET['sq']	== 'salvalayoutazregistrospendentes' ) {
		
	$sql = "
		update  tusuario
		set 
			layoutazregistrospendentes = '$_GET[campo]'
		where usuario = '$_SESSION[usuario]' and 
			contaprincipal = '$_SESSION[contaprincipal]'";	
		
	$resp = pg_exec($sql);
		
// salva o grid  	
} else if ( $_GET['sq']	== 'substatusnox' ) {

		
	$sql = "
		update  tchamada
		set substatus =  'NEG'
		where protocolo = '$_GET[protocolo]' ";	
		
	$resp = pg_exec($sql);
	
				
							
	
} else if ( $_GET['sq']	== 'carregaconta' ) {

	//abre colchetes na variavel $exibetexto pra o javascript entender que e um array
	$exibetexto = "[['','Selecione']";
		
	$sql = 	"
		select 				
			conta,
			nomeconta
		from tconta 
        where contaprincipal = $_GET[contaprincipal]	";		
		
	// se foi enviado grupo, entao faz um filtro por grupo
	// seleciona algumas filiais	
	
	if ($_GET['grupoprincipal'] != '') {
	
		$sql .= 	" and grupoprincipal = '$_GET[grupoprincipal]' ";
	
	}else if ($_GET['grupo'] != '') {

		$sql .= 	" and grupo = '$_GET[grupo]' ";
	}
		
	$sql .= 	" order by nomeconta	";	
		
		
		
	$resp = pg_exec($sql);

	if ( pg_numrows($resp) > 0 ){

		for ($i=0;$i<pg_numrows($resp);$i++) {
 		    $arr=pg_fetch_array($resp, $i,PGSQL_ASSOC);
			$exibetexto .= ",['$arr[conta]','$arr[nomeconta]']";		
		}		
		
  	    $exibetexto .= "]";

    }
	
	echo $exibetexto;	

	
}  

//$_GET[protocolo]
function emailrespostaconsulta($protocolo){

	echo "<meta http-equiv='Content-type' content='text/html; charset=UTF-8'>";

	$detalhescore = '';	

    // validade do terceiro, pego a datadesaida de tchamada e adiciono 24 horas.  
	$dataFinal = '';
	// validade do terceiro que e 24 horas
	$validade = '';
	//codigo sql
	$codresp = 'Dados nao localizados, por favor contacte o administrador de sistemas (INTERRISCO), codigo do erro:  2716 /emailderesposta/' ;
	// para colocar no cabecalho do email, pode nao haver nome de pessoa so placa
	$nomeemail = '-';
  
  	$sql = "				
		select 
			tchamada.senha,
			tchamada.placacarro,
			tchamada.placareboque,
			tchamada.placasemireboque,			
			tchamada.placaterceiroreboque,			
			tchamada.validade,
			tchamada.codipessoa,
			tchamada.usuario,
			tchamada.resposta,
			tchamada.statusprotocolo,		
			tchamada.liberado as apto,	
			tchamada.datasaida,
			tchamada.tipovinculo,
            tchamada.obsresposta,
			tchamada.pacote,
			tconta.nomeconta,			
			tconta.grupo,
			tconta.email,
			tcontaprincipal.maillogo,
			tcontaprincipal.mailuser,
			tcontaprincipal.mailsenha,
			tcontaprincipal.mailhost,
			tchamada.conta as conta,
			tchamada.contaprincipal as contaprincipal,			
			cast(tchamada.dataentrada as date)  + 30 as dataentradatrinta,		
			to_char(dataentrada, 'DD/MM/YY  HH24:MI') as dataentrada,
            to_char(tchamada.dataentrada, 'DD-MM-YYYY  HH24:MI') as dataentradacalculo,
            tparametrocadastro.diasvigenciaconsulta,
			tcontaprincipal.respostacompletacadastro
		from
			tconta,
			tchamada,
			tcontaprincipal,
            tparametrocadastro
		where tchamada.conta = tconta.conta  and
            tchamada.conta = tparametrocadastro.conta and
			tchamada.contaprincipal = tcontaprincipal.contaprincipal and
			tchamada.protocolo = '$protocolo' ";
	
	$res = pg_exec($sql);
	// esta parte monta o relatorio
	
	if ( pg_numrows($res) > 0 ){
 
		$arr = pg_fetch_array($res,0,PGSQL_ASSOC);
		
//
 //$arr['respostacompletacadastro'] = 'Sim';				
//
	
		if ($arr['respostacompletacadastro'] == 'Sim') {	
		
			$detalhescore = "<table border=1 align='center'>";
			$detalhescore .= "<tr><td colspan=2 align=center>Detalhamento pesquisa</td></tr>";
			$detalhescore .= "<tr><td>Iten</td><td> Resultado</td></tr>";
			
		}
	
        // consulta 24 horas mas tem cliente com 90 dias de vigencia para consulta
        $$arr['diasvigenciaconsulta'] = (int)$arr['diasvigenciaconsulta'];
        
        // se for zero, coloca como vigencia 24 horas
        if ($arr['diasvigenciaconsulta'] < 1)
            $arr['diasvigenciaconsulta'] = 1;          
  
		if (strlen($arr['codipessoa']) > 2 ) {

			// este select serve para trazer os dados do motorista e o tipo de vinculo, e tb a checagem do motorista.

			$sqlpes = "			
				select 
					tpessoa.nomepessoa,
					tpessoa.cpfcnpj,
					tpessoafisica.categoria
				from
					tpessoa 
					LEFT OUTER JOIN tpessoafisica ON (tpessoa.codipessoa = tpessoafisica.codipessoa) 
				where tpessoa.codipessoa = $arr[codipessoa]  ";
														
			$respes = pg_exec($sqlpes);		
			
			$arrpes = pg_fetch_array($respes,0,PGSQL_ASSOC);
			
			if ($arr['respostacompletacadastro'] == 'Sim') {	
				
				$sqlscore = "select qtdcheque,ckreceita,ckcheque,ckconsultoria,ckfacial,cktj,ckcnh,obs from tvalidapessoa where codipessoa = '$arr[codipessoa]' ";
				$scorepes = pg_exec($sqlscore);	
				
				if ( pg_numrows($scorepes) > 0 ){
									
					if ( pg_result($scorepes,'ckreceita') == 't') {					
						$detalhescore .= "<tr><td>Pesq. Receita Federal</td><td><img src='cid:liberado'>OK</td></tr>";
					}else {
						$detalhescore .= "<tr><td>Pesq. Receita Federal</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
					}

					if ( pg_result($scorepes,'ckcheque') == 't') {					
						$detalhescore .="<tr><td>Pesq. Socio economica</td><td><img src='cid:liberado'>OK</td></tr>";
					}else if ( pg_result($scorepes,'ckcheque') == 'f') {
						$detalhescore .= "<tr><td>Pesq. Socio economica</td><td> Pendente regularizacao ".pg_result($scorepes,'qtdcheque')." cheque(s)</td></tr>";
					}

					if ( pg_result($scorepes,'cktj') == 't') {					
						$detalhescore .= "<tr><td>Pesq. TJ</td><td><img src='cid:liberado'>OK</td></tr>";
					}else {
						$detalhescore .= "<tr><td>Pesq. TJ</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
					}
					
					if ( pg_result($scorepes,'ckconsultoria') == 't') {					
						$detalhescore .= "<tr><td>Pesq. Criminal</td><td><img src='cid:liberado'>OK</td></tr>";
					}else {
						$detalhescore .= "<tr><td>Pesq. Criminal</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
					}
					
				
					

					if ( pg_result($scorepes,'ckcnh') == 't') {					
						$detalhescore .= "<tr><td>Pesq. CNH</td><td> <img src='cid:liberado'> OK</td></tr>";
					}else {
						$detalhescore .= "<tr><td>Pesq. CNH</td><td> <img src='cid:pendnete'> Pendente regularizacao</td></tr>";
					}                                                                  


					if ( pg_result($scorepes,'ckfacial') == 't') {					
						$detalhescore .= "<tr><td>Pesq. CNH</td><td> <img src='cid:liberado'> OK</td></tr>";
					}else {
						$detalhescore .= "<tr><td>Pesq. CNH</td><td> <img src='cid:pendnete'> Pendente regularizacao</td></tr>";
					}                                                                  

							
					$detalhescore .= "<tr><td colspan=2>".str_replace(";","<br>",pg_result($scorepes,'obs'))."</td></tr>";
					
				}			
			}								
		}

		//****************************************
		//* VEICULO
		//****************************************
		if (strlen($arr['placacarro']) > 0 ) {
			
			$sqlcarro = "			
				select 
					placa,
					marca,
					modelo,
					nomepessoa,
					cpfcnpj
				from
					tpessoa,
					tcarro 
				where tcarro.placa = '$arr[placacarro]'  and
					tcarro.codipessoa = tpessoa.codipessoa ";

			$rescarro = pg_exec($sqlcarro);
						
			if ( pg_numrows($rescarro) > 0 ){

				$arrplacacarro = pg_fetch_array($rescarro,0,PGSQL_ASSOC);
												
				if ($arr['respostacompletacadastro'] == 'Sim') {	
				
					$sqlscore = "select ckpropriedade,ckreceita,ckcheque,ckantt,cklicenciamento,obs from tvalidaplaca where placa = '$arr[placacarro]' ";
					$scorepes = pg_exec($sqlscore);	
					
					if ( pg_numrows($scorepes) > 0 ){
										
						if ( pg_result($scorepes,'ckpropriedade') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Propriedade Veiculo $arr[placacarro]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Propriedade Veiculo $arr[placacarro]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}
				
						if ( pg_result($scorepes,'ckreceita') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Receita Federal Veiculo $arr[placacarro]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Receita Federal Veiculo $arr[placacarro]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}

						if ( pg_result($scorepes,'ckcheque') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Socio economica Veiculo $arr[placacarro]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Socio economica Veiculo $arr[placacarro]</td><td> Pendente regularizacao ".pg_result($scorepes,'qtdcheque')." cheque(s)</td></tr>";
						}

						if ( pg_result($scorepes,'ckantt') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Antt Veiculo $arr[placacarro]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Antt Veiculo $arr[placacarro]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}

						if ( pg_result($scorepes,'cklicenciamento') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Licenciamento Veiculo $arr[placacarro]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Licenciamento Veiculo $arr[placacarro]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}                                                                  
								
						$detalhescore .= "<tr><td colspan=2> ".str_replace(";","<br>",pg_result($scorepes,'obs'))."</td></tr>";
						
					}				
				}				
			}
		}	
        
		//****************************************
		//* reboque
		//****************************************
		if (strlen($arr['placareboque']) > 0 ) {
			
			$sqlreboque = "			
				select 
					placa,
					marca,
					modelo,
					nomepessoa,
					cpfcnpj
				from
					tpessoa,
					tcarro 
				where tcarro.placa = '$arr[placareboque]'  and
					tcarro.codipessoa = tpessoa.codipessoa ";

			$rescreboque = pg_exec($sqlreboque);
						
			if ( pg_numrows($rescreboque) > 0 ){

				$arrplacareboque = pg_fetch_array($rescreboque,0,PGSQL_ASSOC);
				
				
				if ($arr['respostacompletacadastro'] == 'Sim') {	
				
					$sqlscore = "select ckpropriedade,ckreceita,ckcheque,ckantt,cklicenciamento,obs from tvalidaplaca where placa = '$arr[placareboque]' ";
					$scorepes = pg_exec($sqlscore);	
					
					if ( pg_numrows($scorepes) > 0 ){
										
						if ( pg_result($scorepes,'ckpropriedade') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Propriedade Reboque $arr[placareboque]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Propriedade Reboque $arr[placareboque]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}
				
						if ( pg_result($scorepes,'ckreceita') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Receita Federal Reboque $arr[placareboque]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Receita Federal Reboque $arr[placareboque]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}

						if ( pg_result($scorepes,'ckcheque') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Socio economica Reboque $arr[placareboque]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Socio economica Reboque $arr[placareboque]</td><td> Pendente regularizacao ".pg_result($scorepes,'qtdcheque')." cheque(s)</td></tr>";
						}

						if ( pg_result($scorepes,'ckantt') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Antt Reboque $arr[placareboque]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Antt Reboque $arr[placareboque]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}

						if ( pg_result($scorepes,'cklicenciamento') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Licenciamento Reboque $arr[placareboque]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Licenciamento Reboque $arr[placareboque]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}                                                                  
								
						$detalhescore .= "<tr><td colspan=2> ".str_replace(";","<br>",pg_result($scorepes,'obs'))."</td></tr>";
						
					}				
				}				
			}
		}	
        
		//****************************************
		//* semireboque
		//****************************************
		if (strlen($arr['placasemireboque']) > 0 ) {
			
			$sqlsemireboque = "			
				select 
					placa,
					marca,
					modelo,
					nomepessoa,
					cpfcnpj
				from
					tpessoa,
					tcarro 
				where tcarro.placa = '$arr[placasemireboque]'  and
					tcarro.codipessoa = tpessoa.codipessoa ";

			$rescsemireboque = pg_exec($sqlsemireboque);
						
			if ( pg_numrows($rescsemireboque) > 0 ){

				$arrplacasemireboque = pg_fetch_array($rescsemireboque,0,PGSQL_ASSOC);
				
				if ($arr['respostacompletacadastro'] == 'Sim') {	
				
					$sqlscore = "select ckpropriedade,ckreceita,ckcheque,ckantt,cklicenciamento,obs from tvalidaplaca where placa = '$arr[placasemireboque]' ";
					$scorepes = pg_exec($sqlscore);	
					
					if ( pg_numrows($scorepes) > 0 ){
										
						if ( pg_result($scorepes,'ckpropriedade') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Propriedade SemiReboque $arr[placacarro]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Propriedade SemiReboque $arr[placacarro]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}
				
						if ( pg_result($scorepes,'ckreceita') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Receita Federal SemiReboque $arr[placacarro]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Receita Federal SemiReboque $arr[placacarro]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}

						if ( pg_result($scorepes,'ckcheque') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Socio economica SemiReboque $arr[placacarro]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Socio economica SemiReboque $arr[placacarro]</td><td> Pendente regularizacao ".pg_result($scorepes,'qtdcheque')." cheque(s)</td></tr>";
						}

						if ( pg_result($scorepes,'ckantt') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Antt SemiReboque $arr[placacarro]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Antt SemiReboque $arr[placacarro]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}

						if ( pg_result($scorepes,'cklicenciamento') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Licenciamento SemiReboque $arr[placacarro]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Licenciamento SemiReboque $arr[placacarro]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}                                                                  
								
						$detalhescore .= "<tr><td colspan=2> ".str_replace(";","<br>",pg_result($scorepes,'obs'))."</td></tr>";
						
					}
					
				}	
			}
		}			
		
		//****************************************
		//* terceiroreboque
		//****************************************
		if (strlen($arr['placaterceiroreboque']) > 0 ) {
			
			$sqlterceiroreboque = "			
				select 
					placa,
					marca,
					modelo,
					nomepessoa,
					cpfcnpj
				from
					tpessoa,
					tcarro 
				where tcarro.placa = '$arr[placaterceiroreboque]'  and
					tcarro.codipessoa = tpessoa.codipessoa ";

			$rescterceiroreboque = pg_exec($sqlterceiroreboque);
						
			if ( pg_numrows($rescterceiroreboque) > 0 ){

				$arrplacaterceiroreboque = pg_fetch_array($rescterceiroreboque,0,PGSQL_ASSOC);
				
				if ($arr['respostacompletacadastro'] == 'Sim') {	
				
					$sqlscore = "select ckpropriedade,ckreceita,ckcheque,ckantt,cklicenciamento,obs from tvalidaplaca where placa = '$arr[placaterceiroreboque]' ";
					$scorepes = pg_exec($sqlscore);	
					
					if ( pg_numrows($scorepes) > 0 ){
										
						if ( pg_result($scorepes,'ckpropriedade') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Propriedade 3. Reboque $arr[placacarro]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Propriedade 3. Reboque $arr[placacarro]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}
				
						if ( pg_result($scorepes,'ckreceita') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Receita Federal 3. Reboque $arr[placacarro]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Receita Federal 3. Reboque $arr[placacarro]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}

						if ( pg_result($scorepes,'ckcheque') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Socio economica 3. Reboque $arr[placacarro]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Socio economica 3. Reboque $arr[placacarro]</td><td> Pendente regularizacao ".pg_result($scorepes,'qtdcheque')." cheque(s)</td></tr>";
						}

						if ( pg_result($scorepes,'ckantt') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Antt 3. Reboque $arr[placacarro]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Antt 3. Reboque $arr[placacarro]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}

						if ( pg_result($scorepes,'cklicenciamento') == 't') {					
							$detalhescore .= "<tr><td>Pesq. Licenciamento 3. Reboque $arr[placacarro]</td><td><img src='cid:liberado'>OK</td></tr>";
						}else {
							$detalhescore .= "<tr><td>Pesq. Licenciamento 3. Reboque $arr[placacarro]</td><td><img src='cid:pendente'>  Pendente regularizacao</td></tr>";
						}                                                                  
								
						$detalhescore .= "<tr><td colspan=2> ".str_replace(";","<br>",pg_result($scorepes,'obs'))."</td></tr>";
						
					}
					
				}	
			}
		}	
		
		//********************************************
		// monta a resposta
		//*******************************************
		
		$codresp= "<table border='0'  align=center ><tr><td>";
	
		$codresp .= "<table border='0'  align=center width='100%'>";
		
		$codresp .="<tr><td colspan='3' width='100%'><hr color='#000000' size='2' width='100%'></TD></tr>";
		
		if ($arr['apto'] == 't' )  {			
			$codresp .="<tr><td> <b> $arr[maillogo] </td> <td  align='center' > <font size='4'> Situacao: APTO <BR> senha $arr[senha]</td><td  align='right'>  $arr[dataentrada]   </td></tr>";
		
		}else{
			$codresp .="<tr><td> <b>  </td> <td align='center' > <font size='4'> Situacao: PENDENTE REGULARIZACAO </td><td  align='right'>  $arr[dataentrada]  </td></tr>";
			
		}
		
		$codresp .="<tr><td colspan='3' width='100%'><hr color='#000000' size='2' width='100%'></TD></tr>";

		$codresp .= "</table>";
		
		$codresp .="<table  border='0'  align=center width='100%' >";
		$codresp .="<tr><td colspan=2> Comprovante de operacao pesquisa/consulta</td></tr>";
		$codresp .="<tr><td colspan=2> </td></tr>";
		$codresp .="</table>";

		$codresp .="<table   align=center >";
				
		$codresp .="<tr><td> Cliente:  </td><td> $arr[nomeconta]</td>";
		$codresp .="<tr><td> Conta:  </td><td> $arr[conta]</td>";
		
		$codresp .="<tr><td> 		  </td><td> Tipo: $arr[tipovinculo]</td>";
		if (strlen($arr['codipessoa']) > 0 ) {
			$codresp .="<tr><td> Pessoa:  </td><td><b> $arrpes[cpfcnpj] - $arrpes[nomepessoa]</b></td>";
			$codresp .="<tr><td> 		  </td><td> Cat. CNH: $arrpes[categoria]</td>";
			$nomeemail .= $arrpes['nomepessoa'];
		}	
		if (strlen($arr['placacarro']) > 0 ) {
			$codresp .="<tr><td> Veiculo:  </td><td><b> $arrplacacarro[placa] </b> - $arrplacacarro[marca] $arrplacacarro[modelo]</td>";
			$codresp .="<tr><td>  		   </td><td> Prop: $arrplacacarro[nomepessoa] </td>";
			$nomeemail .= ' '.$arrplacacarro['placa'];
		}	
		
		
		if (strlen($arr['placasemireboque']) > 0 ) {
			$codresp .="<tr><td> SemiReboque:  </td><td><b> $arrplacasemireboque[placa] </b>- $arrplacasemireboque[marca] $arrplacasemireboque[modelo]</td>";
			$codresp .="<tr><td>  		   </td><td> Prop: $arrplacasemireboque[nomepessoa] </td>";
			$nomeemail .= ' '.$arrplacasemireboque['placa'];
		}	
		
		if (strlen($arr['placaterceiroreboque']) > 0 ) {
			$codresp .="<tr><td> terceiroReboque:  </td><td><b> $arrplacaterceiroreboque[placa] </b>- $arrplacaterceiroreboque[marca] $arrplacaterceiroreboque[modelo]</td>";
			$codresp .="<tr><td>  		   </td><td> Prop: $arrplacaterceiroreboque[nomepessoa] </td>";
			$nomeemail .= ' '.$arrplacaterceiroreboque['placa'];
		}	

		if (strlen($arr['placareboque']) > 0 ) {
			$codresp .="<tr><td> Reboque:  </td><td><b> $arrplacareboque[placa] </b>- $arrplacareboque[marca] $arrplacareboque[modelo]</td>";
			$codresp .="<tr><td>  		   </td><td> Prop: $arrplacareboque[nomepessoa] </td>";
			$nomeemail .= ' '.$arrplacareboque['placa'];
		}	
				
		// se foi liberado
		if ($arr['apto'] == 't' )  {
		
			// entao verifico se um motorista e terceiro
			if (	$arr['tipovinculo'] == 'AUTONOMO' || 
					$arr['tipovinculo'] == 'AJUDANTE' || 
					$arr['tipovinculo'] == 'TERCEIRO180'  					) {
				
				
				// se for terceiro dou 1 dia de vigencia
				//$dataFinal = mktime(24*1, 0, 0, $mes, $dia, $ano);
				//$validade = date('d/m/Y',$dataFinal);
				//$codresp .="<tr><td> Vigencia:  </td><td> $validade </td>";
				// coloca a vigencia para 30 dias

				$intconta = (int)$arr['conta'];
				
			    //
                // coloco a vigencia da consulta
                //

                $datavalidadeconsulta = strtotime($arr['dataentradacalculo']);                               
                $datavalidadeconsulta = strtotime("+$arr[diasvigenciaconsulta] days", $datavalidadeconsulta);
                
				if ($arr['diasvigenciaconsulta'] == 1) 
                    $codresp .="<tr><td> Vigencia:  </td><td> Liberado para 1 (um) carregamento </td>";
                else
					$codresp .="<tr><td> Vigencia:  </td><td> ".date('d/m/Y',$datavalidadeconsulta)." </td>";
	
	
			// Servis Grupo CSN,terceiro pode ser liberado por 1 semana
			} else if ($arr['tipovinculo'] == 'SEMANAL' ) {
	
                $codresp .="<tr><td> Vigencia:  </td><td> Liberado por 7 (Sete) dias </td>";

			// SE FUNCIONARIO,AGREGADO,TERCEIRO180		
			} else {
				
				// Servis Grupo CSN,terceiro pode ser liberado por 180 DIAS (VICULO TERCEIRO180				
				$codresp .="<tr><td> Vigencia:  </td><td> $arr[validade]</td>";
			}	
			
		}else {
		
			//obs
			$arr['resposta'] = str_replace(";","<br>",$arr['resposta']);
			$codresp .="<tr><td>Obs.:</td><td>$arr[resposta]</td>";

		}
		
      
        //***************************
        //** obsresposta para serasa 100 ou 200 mil
        //***************************		
        $codresp .="<tr><td><br></td><td><br> $arr[obsresposta] <br></td>";        
        
		$codresp .="<tr><td> Autenticacao:  </td><td> ".md5($arr['senha'])."</td>";
		
		$codresp .="<tr><td colspan='3' width='100%'><hr color='#000000' size='2' width='100%'></TD></tr>";
		$codresp .="<tr><td colspan=2 class='letra_gris'>Documento confidencial e interno, sua divulga&ccedil;&atilde;o poder&aacute; implicar em responsabilidade!</td>";
		$codresp .="<tr><td colspan=2 class='letra_gris'>Contratar ou n&atilde;o servi&ccedil;o(s) deste perfil &eacute; uma decis&atilde;o exclusivamente tomada pela cliente ou empresa consultante !</td>";
		if ( $arr['contaprincipal'] == '920771') {
			$codresp .="<tr> <td colspan=2> <br> Pacote integracao $arr[pacote]</td></tr>";				
		}

		$codresp .="</table>";
		$codresp .="<A NAME='liberacao'></A>";
		$codresp .="</table> </td></tr>";
			
	}	
		
	//******************************************	
	// duplico a resposta e coloco um pontilhado
	//******************************************
//if ($arr['respostacompletacadastro'] == 'Sim') {	
		
		//****************
		// detalhe score
		//****************
		$codresp .= "<br>".$detalhescore."</table>";
	
	
//}else{		

	if ($arr['respostacompletacadastro'] != 'Sim') {
		
		$codresp .="<br><hr><br>$codresp";
	
	}		
				
		
	$host = $arr['mailhost'];
	$usuario = $arr['mailuser'];
	$senha = $arr['mailsenha'];
		
	//**************************************
	//*	envio de email pelo phpmailer
	//**************************************
		
	require_once('../-/funcoes/enviaemail.php');	
		
	// funcao esta em /-/funcoes/enviaemail.php
	//enviaemail($host,$usuario,$senha,$destino,$assunto,$msg)
	return enviaemail($host,$usuario,$senha,$arr['email'],$nomeemail,$codresp);
	
}	

function gravapessoaresumido($codipessoa,$nomepessoa,$cpfcnpj) {

	//abre colchetes na variavel $exibetexto pra o javascript entender que e um array
	$nomepessoa = trim(strtoupper($nomepessoa));
			
	$codipessoa = fmaxcodipessoa();	
						
	$sql = 	"
		insert into tpessoa ( tipopessoa,codipessoa,cpfcnpj,nomepessoa,usuario,usuariodatacadastrouficha)
		values ('CPF','$codipessoa','$cpfcnpj','$nomepessoa','".$_SESSION[usuario]."','".date('d/m/Y H:i:s')."')  ";		
	
	$resp = pg_exec($sql);

	if ( $resp ){
		echo $codipessoa;	
	}else{
		echo '';
	}
}

//$_GET[senha]
function exiberesposta($senha){
	
	// validade do terceiro, pego a datadesaida de tchamada e adiciono 24 horas.  
	$dataFinal = '';
	// validade do terceiro que � 24 horas
	$validade = '';
	//codigo sql
	$codresp =''  ;
	$confidencial = '';
  
  	$sql = "				
		select 
			tchamada.protocolo,
			tchamada.senha,
			tchamada.placacarro,
			tchamada.placareboque,
			tchamada.placasemireboque,
			tchamada.placaterceiroreboque,
			tconta.nomeconta,
			tchamada.validade,
			tchamada.codipessoa,
			tchamada.usuario,
			tchamada.resposta,
			tchamada.statusprotocolo,		
			tchamada.liberado,	
			tchamada.datasaida,
			tchamada.tipovinculo,
            tchamada.obsresposta,
			tchamada.pacote,
			tchamada.conta as conta,
            to_char(tchamada.dataentrada, 'DD-MM-YYYY HH24:MI') as dataentradacalculo,
			tchamada.contaprincipal as contaprincipal,
			to_char(tchamada.dataentrada, 'DD/MM/YY  HH24:MI') as dataentrada,
			cast(tchamada.dataentrada as date)  + 30 as dataentradatrinta,		
			to_char((tchamada.datasaida - tchamada.dataentrada), 'HH24:MI') as tempo,			
			tconta.email,
			tconta.grupo,
			tconta.grupoprincipal,
			tconta.cnpjglog,
			tconta.senhaglog,
			tcontaprincipal.maillogo,			
            tparametrocadastro.diasvigenciaconsulta		
        from
			tconta,
			tchamada,
			tcontaprincipal,
            tparametrocadastro
		where tchamada.conta = tconta.conta  and
            tchamada.conta = tparametrocadastro.conta and
			tchamada.contaprincipal = tcontaprincipal.contaprincipal and
			tchamada.senha = '$senha' ";
	
	$res = pg_exec($sql);
	// esta parte monta o relatorio

//echo "view88451515 <br> $sql";
	
	if ( pg_numrows($res) > 0 ){
 
		$arr = pg_fetch_array($res,0,PGSQL_ASSOC);
    
		//*************************************
		// busco o rastreador para a servis CSN
		//*************************************
		$tiporastreamento = '';		
		if ($arr['grupoprincipal'] == 'CSN' ) {						
			$sqlpegatiporastreamento = "
			
				select tiporastreamento 
				from tiporastreamento 
				where protocolo = $arr[protocolo]
				
			";						

			$restiporastreamento = pg_exec($sqlpegatiporastreamento);
			if ( pg_numrows($restiporastreamento) > 0 ){		
				$tiporastreamento = "Tipo Rastreamento: ".pg_result($restiporastreamento,'tiporastreamento');
			}	
		}
		

        // consulta 24 horas mas tem cliente com 90 dias de vigencia para consulta
        $arr['diasvigenciaconsulta'] = (int)$arr['diasvigenciaconsulta'];
        
        // se for zero, coloca como vigencia 24 horas
        if ($arr['diasvigenciaconsulta'] < 1)
            $arr['diasvigenciaconsulta'] = 1;    

  
		if (strlen($arr['codipessoa']) > 0 ) {

			// este select serve para trazer os dados do motorista e o tipo de vinculo, e tb a checagem do motorista.

			$sqlpes = "			
				select 
					tpessoa.nomepessoa,
					tpessoa.cpfcnpj,
					tpessoa.copiadoc,
					tpessoafisica.categoria
				from
					tpessoa 					
					LEFT OUTER JOIN tpessoafisica ON (tpessoa.codipessoa = tpessoafisica.codipessoa) 					
	
				where tpessoa.codipessoa = $arr[codipessoa]  ";

			
				
			$respes = pg_exec($sqlpes);			
			$arrpes = pg_fetch_array($respes,0,PGSQL_ASSOC);


			//****************************
			//* pega os arquivos de upload
			//****************************
			$doc ="";	
			if ( strlen ($arr['copiadoc'] ) > 2 ) {
								
				$pieces = explode(";", $arrpes['copiadoc']);
				foreach($pieces as $arq){				
					if ($arq != '')	
						$doc .="<br><a href='../0uploaddoc/$arq' target='_blank'>".substr($arq,50)."</a> ";
				}				
			}

			//***********************************************
			// AVISOS 
			//***********************************************

			$sqlpesocorrencia = "
				select obs,
					to_char(datacriacao, 'DD/MM/YY') as datacriacao,
					usuario
				from tocorrencia
				where chavedebusca = '$arrpes[cpfcnpj]'
				order by codiocorrencia desc";

			$ressqlopescorrencia = pg_exec($sqlpesocorrencia);	

			if ( pg_numrows($ressqlopescorrencia) > 0 ) {

				$avisos .= "<br><img src='../0bmp/liberacao.png' width='20' height='20'  border='0' align='absmiddle'> <b>ATENCAO ! </b>Alerta este motorista possui avisos no sitema ";
				$avisos .= "<br><textarea readonly COLS=120 ROWS=3>";
				
				for ($i=0; $i < pg_numrows($ressqlopescorrencia ); $i++) {

					$arrpesocorrencia = pg_fetch_array($ressqlopescorrencia,$i,PGSQL_ASSOC);
					$avisos .= "$arrpesocorrencia[datacriacao] $arrpesocorrencia[usuario] - $arrpesocorrencia[obs] \n";
				}
				
				$avisos .= "</textarea>";

			}
		}
		
		//****************************************
		//* VEICULO
		//****************************************
		if (strlen($arr['placacarro']) > 0 ) {
			
			$sqlcarro = "			
				select 
					placa,
					marca,
					modelo,
					tcarro.copiadoc,
					nomepessoa,
					cpfcnpj
				from
					tpessoa,
					tcarro 
				where tcarro.placa = '$arr[placacarro]'  and
					tcarro.codipessoa = tpessoa.codipessoa ";

			$rescarro = pg_exec($sqlcarro);
						
			if ( pg_numrows($rescarro) > 0 ){

				$arrplacacarro = pg_fetch_array($rescarro,0,PGSQL_ASSOC);
				
				
			}
		}	
        
		//****************************************
		//* reboque
		//****************************************
		if (strlen($arr['placareboque']) > 0 ) {
			
			$sqlreboque = "			
				select 
					placa,
					marca,
					modelo,
					tcarro.copiadoc,
					nomepessoa,
					cpfcnpj
				from
					tpessoa,
					tcarro 
				where tcarro.placa = '$arr[placareboque]'  and
					tcarro.codipessoa = tpessoa.codipessoa ";

			$rescreboque = pg_exec($sqlreboque);
						
			if ( pg_numrows($rescreboque) > 0 ){

				$arrplacareboque = pg_fetch_array($rescreboque,0,PGSQL_ASSOC);
				
				
			}
		}	
        
		//****************************************
		//* semireboque
		//****************************************
		if (strlen($arr['placasemireboque']) > 0 ) {
			
			$sqlsemireboque = "			
				select 
					placa,
					marca,
					modelo,
					tcarro.copiadoc,
					nomepessoa,
					cpfcnpj
				from
					tpessoa,
					tcarro 
				where tcarro.placa = '$arr[placasemireboque]'  and
					tcarro.codipessoa = tpessoa.codipessoa ";

			$rescsemireboque = pg_exec($sqlsemireboque);
						
			if ( pg_numrows($rescsemireboque) > 0 ){

				$arrplacasemireboque = pg_fetch_array($rescsemireboque,0,PGSQL_ASSOC);
				
				
			}
		}	
		
		
		//****************************************
		//* terceiroreboque
		//****************************************
		if (strlen($arr['placaterceiroreboque']) > 0 ) {
			
			$sqlterceiroreboque = "			
				select 
					placa,
					marca,
					modelo,
					tcarro.copiadoc,
					nomepessoa,
					cpfcnpj
				from
					tpessoa,
					tcarro 
				where tcarro.placa = '$arr[placaterceiroreboque]'  and
					tcarro.codipessoa = tpessoa.codipessoa ";

			$rescterceiroreboque = pg_exec($sqlterceiroreboque);
						
			if ( pg_numrows($rescterceiroreboque) > 0 ){

				$arrplacaterceiroreboque = pg_fetch_array($rescterceiroreboque,0,PGSQL_ASSOC);
				
				
			}
		}	
				
		$codresp =" Data do impresso: ".date('d/m/Y H:i:s');
		
		
		$codresp .="<table border='0' width='100%'  align=center ><tr><td>";	
		$codresp .= "<table border='0'  align=center width='100%'>";		
		$codresp .="<tr><td colspan='3' width='100%'><hr color='#000000' size='2' width='100%'></TD></tr>";
		
		if ($arr['liberado'] == 't' )  {
			$codresp .="<tr><td><img src='../0bmp/$arr[maillogo].png' align='absmiddle'  width='60' height='25'> &nbsp;&nbsp;<b>   $arr[maillogo] </b></td> <td  align='center' > <font size='4'> Situacao: <b>APTO </b> senha $arr[senha]</td><td  align='right'> $arr[dataentrada] sla:($arr[tempo]) </td></tr>";
			
		}else{
			$codresp .="<tr><td > &nbsp;&nbsp; <b>   </b></td> <td align='center' > <font size='4'> Situacao: <b> PENDENTE REGULARIZACAO</b> </td><td  align='right'>$arr[dataentrada] sla:($arr[tempo]) </td></tr>";
			
		}
		
		$codresp .="<tr><td colspan='3' width='100%'><hr color='#000000' size='2' width='100%'></TD></tr>";

		$codresp .= "</table>";

		$codresp .="<table width='100%' border='0' align=center >";
				
		$codresp .="<tr><td> Cliente:  </td><td> $arr[nomeconta]</td><td rowspan=6 algin=center>Foto <br> <img src='../0foto/$arrpes[cpfcnpj]' width='114' height='152'  border='0' align='absmiddle'></td></tr>";
		$codresp .="<tr><td> Conta:  </td><td> $arr[conta] -  $tiporastreamento </td></tr>";
		
		$codresp .="<tr><td> 		  </td><td> </td>";
		if (strlen($arr['codipessoa']) > 0 ) {
			$codresp .="<tr><td> Pessoa:  </td><td> <b>$arrpes[cpfcnpj] - $arrpes[nomepessoa] </b></td>";
			$codresp .="<tr><td> 		  </td><td> Tipo: $arr[tipovinculo] Cat. CNH: $arrpes[categoria]</td>";

		}	
		if (strlen($arr['placacarro']) > 0 ) {
			$codresp .="<tr><td> Veiculo:  </td><td> <b>$arrplacacarro[placa]</b> - $arrplacacarro[marca] $arrplacacarro[modelo]</td>";
			$codresp .="<tr><td>  		   </td><td> Prop: $arrplacacarro[nomepessoa] </td>";
		}	
		
		if (strlen($arr['placasemireboque']) > 0 ) {
			$codresp .="<tr><td> SemiReboque:  </td><td> <b>$arrplacasemireboque[placa] </b>- $arrplacasemireboque[marca] $arrplacasemireboque[modelo]</td>";
			$codresp .="<tr><td>  		   </td><td> Prop: $arrplacasemireboque[nomepessoa] </td>";
		}	
		
		if (strlen($arr['placaterceiroreboque']) > 0 ) {
			$codresp .="<tr><td> terceiroReboque:  </td><td> <b>$arrplacaterceiroreboque[placa] </b>- $arrplacaterceiroreboque[marca] $arrplacaterceiroreboque[modelo]</td>";
			$codresp .="<tr><td>  		   </td><td> Prop: $arrplacaterceiroreboque[nomepessoa] </td>";
		}	
		
		if (strlen($arr['placareboque']) > 0 ) {
			$codresp .="<tr><td> Reboque:  </td><td> <b>$arrplacareboque[placa] </b>- $arrplacareboque[marca] $arrplacareboque[modelo]</td>";
			$codresp .="<tr><td>  		   </td><td> Prop: $arrplacareboque[nomepessoa] </td>";
		}	
		
		
		// se foi liberado
		if ($arr['liberado'] == 't') {
			
			// entao verifico se um motorista e terceiro
			if (
					$arr['tipovinculo'] == 'AUTONOMO' || 
					$arr['tipovinculo'] == 'AJUDANTE' || 
					$arr['tipovinculo'] == 'TERCEIRO180'  					) {

				// se for terceiro dou 1 dia de vigencia
				//$dataFinal = mktime(24*1, 0, 0, $mes, $dia, $ano);
				//$validade = date('d/m/Y',$dataFinal);
				//$codresp .="<tr><td> Vigencia:  </td><td> $validade </td>";
				
				$intconta = (int)$arr['conta'];
   
                //
                // coloco a vigencia da consulta
                //                                
                
                $datavalidadeconsulta = strtotime($arr['dataentradacalculo']);                               
                $datavalidadeconsulta = strtotime("+$arr[diasvigenciaconsulta] days", $datavalidadeconsulta);               
                
				if ($arr['diasvigenciaconsulta'] == 1) 
                    $codresp .="<tr><td> Vigencia: </td><td> Liberado para 1 (um) carregamento </td>";
                else
					$codresp .="<tr><td> Vigencia:  </td><td> ".date('d/m/Y',$datavalidadeconsulta)." </td>";
					
				
			// Servis Grupo CSN,terceiro pode ser liberado por 1 semana
			} else if ($arr['tipovinculo'] == 'SEMANAL' ) {
	
                $codresp .="<tr><td> Vigencia:  </td><td> Liberado por 7 (Sete) dias </td>";

			// SE FUNCIONARIO,AGREGADO,TERCEIRO180		
			} else {
				
				// Servis Grupo CSN,terceiro pode ser liberado por 180 DIAS (VICULO TERCEIRO180				
				$codresp .="<tr><td> Vigencia:  </td><td> $arr[validade]</td>";
			}			
			
		}else {
		
			//obs
			if ($arr[resposta] != '') {
				
				$codresp .="<tr><td>Obs.:</td><td><textarea id='obsresposta' COLS=60 ROWS=4>$arr[resposta]</textarea>  </td>";

	
			}
			
		}
 			$confidencial = "<tr><td colspan=2 class='letra_gris'>Documento confidencial e interno, sua divulga&ccedil;&atilde;o poder&aacute; implicar em responsabilidade!</td>
							 <tr><td colspan=2 class='letra_gris'>Contratar ou n&atilde;o servi&ccedil;o(s) deste perfil &eacute; uma decis&atilde;o exclusivamente tomada pela cliente ou empresa consultante ! </td>";
       
        //  mostra alguma observacao da resposta
		$codresp .="<tr><td> Obs:  </td><td> $arr[obsresposta]</td>";
		
		$codresp .="<tr><td> Autenticacao:  </td><td> ".md5($arr['senha'])."  - Layout:7856a </td>";
		
		$codresp .="<tr><td colspan='3' width='100%'><hr color='#000000' size='2' width='100%'></TD></tr>";
		
		$codresp .="$confidencial";
	
		if ( $arr['contaprincipal'] == '920771') {
			$codresp .="<tr> <td colspan=2> <br> Pacote integracao $arr[pacote]</td></tr>";				
		}
		
		$codresp .="</table>";
		$codresp .="<A NAME='liberacao'></A>";
		$codresp .="</table> </td></tr>";
	
		$email = trim($arr['email']);

//		$codresp .="<div id='divregistros'> </div> <BR><table class='tabla_cabecera' border='0'  align=center  > </div>";
		
		$codresp .="</table>";	 

	// duplico a resposta para sair na mesma folha
		$codresp .= "$codresp";
		
		$codresp .= "Assinatura: _______________________________________________<br>";
		
		$codresp .="<table width='100%' class='tabla_cabecera' align=center ><tr><td>Email: ";
		$email=  preg_split ('/[\s,;]+/', $email);
		//var_dump($dest); visualiza array

		for ($i = 0; $i < count($email); $i++) {

			$codresp .=$email[$i]." ";
		
		}
		
		$codresp .="  <a class='btn btn-info btn-md' href='#' role='button' onclick=\"onclick=emailrespostaconsulta('$arr[protocolo]','$email')\"> Clique aqui para enviar email</a></td></tr></table>";
//		$codresp .="<tr><td><input type='submit' class='botao' value='Enviar Email' name='submit' id='submit' onclick=emailrespostaconsulta('$arr[protocolo]','$email')><td></tr></table>";			
		

		//**************
		// replicacao 
		// chave shuttle
		//***************

		if ($arr[cnpjglog] != '') {
			
			//servis csn
			if ( $arr[grupoprincipal] == 'CSN' ) {
				echo replicacao($arr[grupo],$arr[protocolo],$arr[cnpjglog],$arr[senhaglog],'csn.servisgr.com.br:12126');		
				//echo replicacao($arr[grupo],$arr[protocolo],$arr[cnpjglog],$arr[senhaglog],'186.225.18.161:12126');		
			//servis	
			}else if ( $arr[contaprincipal] == '855705' ) {							
				echo replicacao($arr[grupo],$arr[protocolo],$arr[cnpjglog],$arr[senhaglog],'csn.servisgr.com.br:12121');
			}
//			else if ( $arr[contaprincipal] == '908311' ) //cci
//				echo replicacao($arr[grupo],$arr[protocolo],$arr[cnpjglog],$arr[senhaglog],'189.17.157.85:12121');    
		}
	
	
//	echo " <br>teste <br>".$arr['cnpjglog']."... ".$arr['grupoprincipal'];
	
		// nox 920771
		if ( $arr[contaprincipal] == '920771' ) {
		
			//echo envioocorrencia($arr['conta'],$_GET[protocolo],$arr['pacote'],$arr['liberado'],date('d/m/Y',$datavalidadeconsulta),$arr['codipessoa'],$arrpes['cpfcnpj'],$arrplacacarro['placa'],$arrplacareboque['placa'],$arrplacareboque['placa'],$arrplacaterceiroreboque['placa']).' <br> ';
					
		}
			
		//$codresp .= ftela(' 0interrisco_funajax.mostraresposta');
	
	}	
	
	return "$doc $avisos  $codresp ";
	
}	




// vinculo um pesquisador da ficha que esta iniciando a consulta
function vinculapesquisador($protocolo) {
      
	$sqlpesquisador = "			
		update tchamada
		set pesquisador = '$_SESSION[usuario]'
		where protocolo = $protocolo";
	
	$respesquisador = pg_exec($sqlpesquisador);
	
}

//***********************221022
//verifico se precisa enviar para o consultoria ou nao
//****************************

function botaoconsultoria($codipessoa,$tipovinculo,$contaprincipal,$conta) {


	// se for funcionario faz o consultoria em um ano se for agregado ou terceiro em 6 meses	
	//if ($tipovinculo == 'FUNCIONARIO' || $tipovinculo == 'INTERNO' ) 
//	$menosdias = date("d/m/Y H:i:s", strtotime("-360 days") );
	//else 	
		//$menosdias = date("d/m/Y H:i:s", strtotime("-180 days") );


	$sqlconsultoria = "
		select to_char((current_timestamp - data  ), 'DD') as diasconsultoria
		from tcontroleconsultoria
		where codipessoa = $codipessoa  
		order by data desc";
	
	$ressqlconsultoria = pg_exec($sqlconsultoria);	
	
	$enviar = false;
	
	if ( pg_numrows($ressqlconsultoria) > 0 ){
	
		$arr = pg_fetch_array($ressqlconsultoria,0,PGSQL_ASSOC);
		
		if ( (int)$arr['diasconsultoria'] > 365 ) {
			$enviar = true;
		}else{
			$enviar = false;
		}
					
	}else{
		
		$enviar = true;
		
	}	
	
	if ($enviar) {
		//************************************************
		// envio de email    rad dtpr mvrj
		//************************************************
		
		$z= " <a href='#' class='btn btn-success btn-sm mb-1' onclick=gravacontroleconsultoria('interrisco@gmail.com','a34$5y00','$codipessoa','MANUAL','caiooliveiracentury@gmail.com') >       <img src='../0bmp/t.png' align='absmiddle'  width='25' height='25'> MANUAL </a><br>";
		$z.= " <a href='#' class='btn btn-warning btn-sm mb-1' onclick=gravacontroleconsultoria('enviodtpr@gmail.com','grcent2015','$codipessoa','PERFIL','especial1@sra22.com')>  			<img src='../0bmp/perfil.png' align='absmiddle'  width='25' height='25'> PERFIL+ </a><br> ";
//		$z.= " <a href='#' class='btn btn-light btn-sm mb-1' onclick=gravacontroleconsultoria('enviomvrj@gmail.com','grcent2015','$codipessoa','MVRJ','pratavermelha@gmail.com') >        <img src='../0bmp/mvrj.png'  align='absmiddle' width='25' height='25'>.RioJan</a><br>	";
		$z.= " <a href='#' class='btn btn-warning btn-sm mb-1' onclick=gravacontroleconsultoria('enviordpr@gmail.com','grcent2015','$codipessoa','RADPR','consultasrh01@gmail.com') >          <img src='../0bmp/rad.png'  align='absmiddle' width='25' height='25'> RAD000. </a><br>";
  		//$z.= " <a href='#' onclick=gravacontroleconsultoria('worklord@gmail.com','zxcv1596','$codipessoa','MANUAL','caiooliveiracentury@gmail.com') > <img src='../0bmp/manual.png' align='absmiddle'  width='25' height='25'>.Manual</div></a><br>";
			
		return $z;	
		
	}	

}

//verifica qual o tipo de vinculo do motorista
function verpessoavinculo($chave,$contaprincipal,$conta){

	IF ($contaprincipal != '') {

		$sql = "
			select tipovinculo
			from tpessoavinculo
			where tpessoavinculo.codipessoa = $chave and
				tpessoavinculo.contaprincipal = $contaprincipal and
				tpessoavinculo.conta = $conta	";
		
		$res = pg_exec($sql);
		
		if (pg_numrows($res) > 0 ){	
		
			return pg_result($res,'tipovinculo');
			
		}else{
			return '';	
		}	
		
	}
		
} 

function vercarrovinculo($chave,$contaprincipal,$conta){

	$sql = "
		select tipovinculo
		from tcarrovinculo
		where tcarrovinculo.placa = '$chave' and
			tcarrovinculo.contaprincipal = $contaprincipal and
			tcarrovinculo.conta = $conta
		";
	
	$res = pg_exec($sql);
	
	if (pg_numrows($res) > 0 ){	
	
		return pg_result($res,'tipovinculo');
		
	}else{
		return '';	
	}	
			
		
} 

function RemoveAcentos($frase){
 
 
	// assume $str esteja em UTF-8
	$map = array(
		'�' => 'a',
		'�' => 'a',
		'�' => 'a',
		'�' => 'a',
		'�' => 'e',
		'�' => 'e',
		'�' => 'i',
		'�' => 'o',
		'�' => 'o',
		'�' => 'o',
		'�' => 'u',
		'�' => 'u',
		'�' => 'c',
		'�' => 'A',
		'�' => 'A',
		'�' => 'A',
		'�' => 'A',
		'�' => 'E',
		'�' => 'E',
		'�' => 'I',
		'�' => 'O',
		'�' => 'O',
		'�' => 'O',
		'�' => 'U',
		'�' => 'U',
		'�' => 'C'
	);
 
	return  strtr($frase,$map); // funciona corretamente
  //$frase = preg_match("/[^a-zA-Z0-9_]/", "",strtr($frase, "�������������������������� ","aaaaeeiooouucAAAAEEIOOOUUC_"));
  
  //$frase = str_replace("_"," ",$frase);
  
  
}

function paragrafos($texto)
{
return str_replace("\n\n", "<br><br>&nbsp;&nbsp;&nbsp;&nbsp;", $texto);
}

// serve para dizer se o motorista foi liberado numa reconsulta
// e mostrar se ja ta liberado 
function verlibmot($cpfcnpj,$grupo,&$dataentrada) {

	if ($grupo != '') 
		$grupo = "tconta.grupo = '$grupo' and ";



	$sql="
		select 	
			liberado,
			to_char(dataentrada,'DD/MM/YYYY') as dataentrada			
		from tchamada,
			tpessoa,
			tconta
		where 					
			tchamada.STATUSPROTocolo = 10  and
			tchamada.codipessoa = tpessoa.codipessoa and
			tchamada.conta  = tconta.conta and
			tpessoa.cpfcnpj  = '$cpfcnpj' and
			$grupo
			liberado is not null
		order by dataentrada desc			
		";
				
	$res= pg_exec($sql);
	  						
	if (pg_numrows($res) > 0 )  {             
		              
				
		$dataentrada  = pg_result($res,'dataentrada');
		return pg_result($res,'liberado');	
		
	}else{
		$dataentrada  = '';
		return null;
	}	

//log de arquivo
//$f = fopen('../log/log$cpfcnpj.txt', 'w'); fwrite($f,"\n select \n $sql \n dataentrada \n $dataentrada  \n returnliberado  \n ".pg_result($res,'liberado') ); fclose($f);	
		
		
}

// serve para dizer se o motorista foi liberado numa reconsulta
// e mostrar se ja ta liberado 
function verlibcarro($tipocarro,$placa,$grupo,&$dataentrada) {


	if ($grupo != '') 
		$grupo = "tconta.grupo = '$grupo' and ";

	$sql="
		select 	
			liberado,
			to_char(dataentrada,'DD/MM/YYYY') as dataentrada			
		from tchamada,
			tconta
		where 			
			tchamada.STATUSPROTocolo = 10  and
			tchamada.conta  = tconta.conta and
			tchamada.placa$tipocarro  = '$placa' and
			$grupo
			liberado is not null
		order by dataentrada desc			
		";
				
	$res= pg_exec($sql);
	  						
	if (pg_numrows($res) > 0 )  {             
		              				
		$dataentrada  = pg_result($res,'dataentrada');
		return pg_result($res,'liberado');	
	}else{
		$dataentrada  = '';
		return null;
	}	
	
//log de arquivo
//$f = fopen('../log/log$placa.txt', 'w'); fwrite($f,"\n select \n $sql \n dataentrada \n $dataentrada  \n returnliberado  \n ".pg_result($res,'liberado') ); fclose($f);	
	
	
}


function envioocorrencia($conta,$protocolo,$pacote,$liberado,$validade,$codipessoa,$cpf,$placacarro,$placareboque,$placasemireboque,$placaterceiroreboque){
	
	
	
	if ($codipessoa != '') {
	
	$sql = "select * from tvalidapessoa 
			where codipessoa = $codipessoa
			order by ckdata desc	";
	}	
	
	$res = pg_exec($sql);
	
	if ( pg_numrows($res) > 0 ){	
				
		for ($i=0; $i < pg_numrows($res ); $i++) {
 
			$arr = pg_fetch_array($res,$i,PGSQL_ASSOC);
	
		}
		
	}	
		
	
	
/*	$client = new nusoap_client("http://200.205.153.234:12121/soap/IIntegraViagens");
	$client = new nusoap_client("http://$ipporta/soap/IIntegraViagens");
	$error = $client->getError();
			
	if ($error) {
		
		$msgreplic .="<h2>Constructor error</h2><pre>" . $error . "</pre>";
			
	}
	
	if ( $liberado == 'f') {	
	
		$validade = date('d/m/Y', strtotime($data. ' - 1 days'));		

	}
	
	$validade = substr($validade, 6,4).'-'.substr($validade, 3, 2).'-'.substr($validade, 0, 2).'T00:00:00';
*/	
	if (true) {
		
			
			
		/*
		$result = $client->call("RespostaSistema", 
			"  	<oResp xsi:type='urn:Resp' xmlns:urn='urn:IntegraIntf'>
					<conta xsi:type='xsd:int'>$conta</conta>
					<pacote xsi:type='xsd:int'>$pacote</pacote>
					<cpf xsi:type='xsd:int'>$cpf</cpf>
					<placacarro xsi:type='xsd:string'>$placacarro</placacarro>
					<placareboque xsi:type='xsd:string'>$placareboque</placareboque>
					<placasemireboque xsi:type='xsd:string'>$placasemireboque</placasemireboque>
					<placaterceiroreboque xsi:type='xsd:string'>$placaterceiroreboque</placaterceiroreboque>
					<liberado xsi:type='xsd:string'>$liberado</liberado>
					<senha xsi:type='xsd:string'>$senha</senha>
					<validade xsi:type='xsd:string'>$validade</validade>				
				</oResp>				
		");	
		*/
		$result = "
			  	<oResp xsi:type='urn:Resp' xmlns:urn='urn:IntegraIntf'>
					<conta xsi:type='xsd:int'>$conta</conta>
					<pacote xsi:type='xsd:int'>$pacote</pacote>
					<cpf xsi:type='xsd:int'>$cpf</cpf>					
					<placacarro xsi:type='xsd:string'>$placacarro</placacarro>
					<placareboque xsi:type='xsd:string'>$placareboque</placareboque>
					<placasemireboque xsi:type='xsd:string'>$placasemireboque</placasemireboque>
					<placaterceiroreboque xsi:type='xsd:string'>$placaterceiroreboque</placaterceiroreboque>
					<liberado xsi:type='xsd:string'>$liberado</liberado>
					<senha xsi:type='xsd:string'>$senha</senha>
					<validade xsi:type='xsd:string'>$validade</validade>				
					<ck xsi:type='xsd:string'>$arr[ck]</ck>
					<ckdata xsi:type='xsd:string'>$arr[ckdata]</ckdata>
					<ckcheque xsi:type='xsd:string'>$arr[ckcheque]</ckcheque>
					<ckchequedata xsi:type='xsd:string'>$arr[ckchequedata]</ckchequedata>
					<qtdcheque xsi:type='xsd:string'>$arr[qtdcheque]</qtdcheque>
					<ckconsultoria xsi:type='xsd:string'>$arr[ckconsultoria]</ckconsultoria>
					<consultoriadata xsi:type='xsd:string'>$arr[consultoriadata]</consultoriadata>
					<cnhdata xsi:type='xsd:string'>$arr[cnhdata]</cnhdata>	
					<ckcnh xsi:type='xsd:string'>$arr[ckcnh]</ckcnh>
					<ckreceita xsi:type='xsd:string'>$arr[ckreceita]</ckreceita>
					<receitadata xsi:type='xsd:string'>$arr[receitadata]</receitadata>					
					<obs xsi:type='xsd:string'>$arr[obs]</obs>
					<reservado1 xsi:type='xsd:string'></reservado1>
					<reservado2 xsi:type='xsd:string'></reservado2>
					<reservado3 xsi:type='xsd:string'></reservado3>
					<reservado4 xsi:type='xsd:string'></reservado4>
				</oResp>				
		";	

	/*	if ($client->fault) {
			
			$msgreplic .="Veiculo ".$result['Codigo']." ".$result['Descricao'];	
			print_r($result);
		}
		else {
			
			$error = $client->getError();
			
			if ($error) {
				$msgreplic .=" Veiculo $placacarro ".$result['Descricao'];	
				echo "<pre>" . $error . "</pre>";
			}
			else {
			   
				$msgreplic .=" Veiculo $placacarro ".$result['Descricao'];	
				
			   
			}
		}	
*/
		
	}	
	
	echo $result;
		
}	

function replicacao($grupoprincipal,$protocolo,$cnpjglog,$senhaglog,$ipporta) {

//replicacaoservis
//return true;

	$replica = 'f';

	if ($replica == 't' ) {
		
		$msgreplic = '';
				
		/*motorista: cliente,nome,cpf,telefone residencial,telefone celular,endere�o*/
		
		$sql="
			select 	
				tchamada.validade,
				tchamada.liberado,
				tchamada.senha,
				tchamada.placacarro,
				tchamada.placareboque,
				tchamada.placasemireboque,
				tchamada.tipovinculo,
				tchamada.placaterceiroreboque,
				to_char(tchamada.datasaida, 'YYYY-MM-DD' ) as validadeterceiro,				
				tcarro.categoria,
				tcarro.anofabricacao,
				tcarro.cor,
				tcarro.marca,
				tcarro.modelo,
				tpessoa.nomepessoa,
				tpessoa.cpfcnpj,
				tpessoa.fone,
				tpessoa.endereco,
				tpessoa.tipopessoa,
				tpessoa.celular,
				treboque.marca as reboquemarca,
				treboque.modelo as reboquemodelo,
				treboque.cor as reboquecor,
				treboque.anofabricacao as reboqueanofabricacao,
				tsemireboque.marca as semireboquemarca,
				tsemireboque.modelo as semireboquemodelo,
				tsemireboque.cor as semireboquecor,
				tsemireboque.anofabricacao as semireboqueanofabricacao,
				tterceiroreboque.marca as terceiroreboquemarca,
				tterceiroreboque.modelo as terceiroreboquemodelo,
				tterceiroreboque.cor as terceiroreboquecor,
				tterceiroreboque.anofabricacao as terceiroreboqueanofabricacao
				
			from tchamada LEFT OUTER JOIN tpessoa ON (tpessoa.codipessoa = tchamada.codipessoa)
				 LEFT OUTER JOIN tcarro ON (tcarro.placa = tchamada.placacarro) 			 
				  LEFT OUTER JOIN tcarro as treboque ON (treboque.placa = tchamada.placareboque)
				  LEFT OUTER JOIN tcarro as tsemireboque ON (tsemireboque.placa = tchamada.placareboque)
				  LEFT OUTER JOIN tcarro as tterceiroreboque ON (tterceiroreboque.placa = tchamada.placareboque)
				 LEFT OUTER JOIN tpessoa as tproprietariocarro ON ( tchamada.codipropcarro = tproprietariocarro.codipessoa ) 
				 
			where protocolo = '$protocolo' 		
			";
					
		$res= pg_exec($sql);
		
	//echo "view 465454<br> $sql";
		
		if (pg_numrows($res) > 0 ) {             
						  						  
			$validadeterceiro = pg_result($res,'validadeterceiro');				
			$validade = pg_result($res,'validade');				
			$liberado = pg_result($res,'liberado');
			$tipovinculo = pg_result($res,'tipovinculo');
			$senha = pg_result($res,'senha');
			$placacarro = pg_result($res,'placacarro');
			$placareboque = pg_result($res,'placareboque');
			$placasemireboque = pg_result($res,'placasemireboque');
			$placaterceiroreboque = pg_result($res,'placaterceiroreboque');			
			$categoria = pg_result($res,'categoria');	
						
			$cpfcnpj = pg_result($res,'cpfcnpj');
			$nomepessoa = pg_result($res,'nomepessoa');
			$endereco = pg_result($res,'endereco');
			$tipopessoa = pg_result($res,'tipopessoa');			
			$fone = pg_result($res,'fone');
			$celular = pg_result($res,'celular');
	

			
				
	
			if (($tipovinculo == 'FUNCIONARIO') || ($tipovinculo == 'INTERNO'))
				$tipovinculo = 1;
			else  if (($tipovinculo == 'AGREGADO') || ($tipovinculo == 'AUXAGREGADO'))
				$tipovinculo = 2;
			else  
				$tipovinculo = 3;
			
		
			$client = new nusoap_client("http://$ipporta/soap/IIntegraViagens");
			$error = $client->getError();
					
			if ($error) {
				
				$msgreplic .="<h2>Constructor error</h2><pre>" . $error . "</pre>";
					
			}
			
			
			// aqui eu adiciono dias para o terceiro dependendo
			// na csn o terceiro pode valer 180 dias ou 7 dias
			// na cci a pesquisa do terceiro � 90 dias e a consulta vale 1 dia
			// atencao <!> para somar dias a data use strtotime( data ".ponto" dias)
					
			if ( $tipovinculo == 'TERCEIRO180') {
		
				$validade = date('d/m/Y', strtotime($validadeterceiro.'+180 days'));		
		
			} else if ( $tipovinculo == 'SEMANAL') {
				
				$validade = date('d/m/Y', strtotime($validadeterceiro.'+7 days'));		

			} else if ( $tipovinculo == 'TERCEIRO' or $tipovinculo == 'AUTONOMO') {
				
				$validade = date('d/m/Y', strtotime($validadeterceiro.'2 days'));		

			}		
			
			if ( $liberado == 'f') {	
			
				$validade = date('d/m/Y', strtotime($data. ' - 1 days'));		

			}
			
			$validade = substr($validade, 6,4).'-'.substr($validade, 3, 2).'-'.substr($validade, 0, 2).'T00:00:00';
							
			if ($categoria == 'ARTICULADO'){
				$categoria = 1;
			}ELSE IF (	 $categoria == 'IMPLEMENTO') {				
				$categoria = 21;				
			}ELSE IF (	 $categoria == 'TRUCK') {				
				$categoria = 2;
			}else if ($categoria ==  'AUTO') {				
				$categoria = 11;
			}	
			
			//*****************//
			// V E I C U L O * //
			//*****************//
			
			if ($placacarro != '' ) {
				
				$placacarro = substr($placacarro, 0, 3).'-'.substr($placacarro, 3, 4);


/*Veiculo:  cliente,placa,modelo,marca,cor,ano,vinculo,tipo*/

			$marca = pg_result($res,'marca');
			$modelo = pg_result($res,'modelo');
			$cor = pg_result($res,'cor');
			$anofabricacao = pg_result($res,'anofabricacao');		
	

				if ($anofabricacao == '') {
					$anofabricacao = 0;
				}

				if ( $modelo == '') {
					$modelo = 'NAO INFORMADO';
				}	
				
				if ( $marca == '') {
					$marca = 'NAO INFORMADO';
				}	
				
				if ( $cor == '') {
					$cor = 'NAO INFORMADO';
				}	
				
				
				
				$msgxml  = "
				   <oVeiculoInsert xsi:type='urn:VeiculoInsert' xmlns:urn='urn:IntegraViagensIntf'>
							<CodigoCliente xsi:type='xsd:int'>74181</CodigoCliente>
							<Placa xsi:type='xsd:string'>$placacarro</Placa>						
							<TipoVeiculo xsi:type='xsd:int'>$categoria</TipoVeiculo>
							<AdicionarPesquisa xsi:type='xsd:string'>1</AdicionarPesquisa>
							<DataValidadePesquisa xsi:type='xsd:dateTime'>$validade</DataValidadePesquisa>
							<SenhaPesquisa xsi:type='xsd:string'>$senha</SenhaPesquisa>
							<CNPJ xsi:type='xsd:string'>$cnpjglog</CNPJ>
							<Validacao xsi:type='xsd:string'>$senhaglog</Validacao>																							
							<Modelo xsi:type='xsd:string'>$modelo</Modelo>
							<Marca xsi:type='xsd:string'>$marca</Marca>
							<Cor xsi:type='xsd:string'>$cor</Cor>
							<AnoFabricacao xsi:type='xsd:int'>$anofabricacao</AnoFabricacao>
							<AnoModelo xsi:type='xsd:int'>$anofabricacao</AnoModelo>													
							<VinculoContrato xsi:type='xsd:string'>$tipovinculo</VinculoContrato>							
						 </oVeiculoInsert>
				";
				


//grava arquivo log	 
//$f = fopen('../_/'.$placacarro.' repliacao veiculo.txt', 'w');  fwrite($f,"\n $msgxml" );	 fclose($f);
					
				$result = $client->call("InserirVeiculo",$msgxml);	

				if ($client->fault) {
						
					//print_r($result);
					
					$msgreplic .="<br> Resposta do WS: ";
					$msgreplic .="<br> faultcode.... ".$result['faultcode'];
					$msgreplic .="<br> faultstring.. ".$result['faultstring'];
					$msgreplic .="<br> faultactor... ".$result['faultactor'];
					$msgreplic .="<br>";
								
				} else {
					
					$error = $client->getError();
					
					if ($error) {
					
						print_r($error);				
						
						$msgreplic .="<br> Resposta do WS: ";
						$msgreplic .="<br> faultcode.... ".$result['faultcode'];
						$msgreplic .="<br> faultstring.. ".$result['faultstring'];
						$msgreplic .="<br> faultactor... ".$result['faultactor'];
						$msgreplic .="<br>";
						
					} else {
					   
						$msgreplic .=" (Veiculo $placacarro ".$result['Descricao'].") ";	
										   
					}
				}		
			}		
				
				
			//******************//	
			//** R E B O Q U E *//
			//******************//
			
			/*Carreta: cliente, placa,  tipo carroceria,marca,cor,ano*/			
				
			if ($placareboque != '' ) {
				
				



				$reboquemarca = pg_result($res,'reboquemarca');
				$reboquemodelo = pg_result($res,'reboquemodelo');
				$reboquecor = pg_result($res,'reboquecor');
				$reboqueanofabricacao = pg_result($res,'reboqueanofabricacao');

			
			
										
				$placareboque = substr($placareboque, 0, 3).'-'.substr($placareboque, 3, 4);

				if ($reboqueanofabricacao == '') {
					$reboqueanofabricacao = 0;
				}	

				
				if ( $reboquemodelo == '') {
					$reboquemodelo = 'NAO INFORMADO';
				}	
				
				if ( $reboquemarca == '') {
					$reboquemarca = 'NAO INFORMADO';
				}	
				
				if ( $reboquecor == '') {
					$reboquecor = 'NAO INFORMADO';
				}	
				
				
				$msgxml  = "
					<oCarretaInsert xsi:type='urn:CarretaInsert' xmlns:urn='urn:IntegraViagensIntf'>
						<CodigoCliente xsi:type='xsd:int'>74181</CodigoCliente>
						<Placa xsi:type='xsd:string'>$placareboque</Placa>
						<Id xsi:type='xsd:int'>0</Id>						
						<CodigoTipoCarroceria xsi:type='xsd:int'>21</CodigoTipoCarroceria>
						<AdicionarPesquisa xsi:type='xsd:string'>1</AdicionarPesquisa>
						<DataValidadePesquisa xsi:type='xsd:dateTime'>$validade</DataValidadePesquisa>
						<SenhaPesquisa xsi:type='xsd:string'>$senha</SenhaPesquisa>
						<CNPJ xsi:type='xsd:string'>$cnpjglog</CNPJ>
						<Validacao xsi:type='xsd:string'>$senhaglog</Validacao>						
						<Marca xsi:type='xsd:string'>$reboquemarca</Marca>
						<Modelo xsi:type='xsd:string'>$reboquemodelo</Modelo>
						<Cor xsi:type='xsd:string'>$reboquecor</Cor>
						<AnoFabricacao xsi:type='xsd:int'>$reboqueanofabricacao</AnoFabricacao>
						<AnoModelo xsi:type='xsd:int'>$reboqueanofabricacao</AnoModelo>
						<TipoCarroceria xsi:type='xsd:string'>21</TipoCarroceria>						
					 </oCarretaInsert>
				";

				$result = $client->call("InserirCarreta", "$msgxml");	
		 
// replicacao reboque
//$f = fopen('../_/'.$placareboque.' repliacao reboque.txt', 'w');	fwrite($f,"\n $msgxml" );	fclose($f);

				if ($client->fault) {
						
					//print_r($result);
					
					$msgreplic .="<br> Resposta do WS: ";
					$msgreplic .="<br> faultcode.... ".$result['faultcode'];
					$msgreplic .="<br> faultstring.. ".$result['faultstring'];
					$msgreplic .="<br> faultactor... ".$result['faultactor'];
					$msgreplic .="<br>";
								
				} else {
					
					$error = $client->getError();
					
					if ($error) {
					
						print_r($error);				
						
						$msgreplic .="<br> Resposta do WS: ";
						$msgreplic .="<br> faultcode.... ".$result['faultcode'];
						$msgreplic .="<br> faultstring.. ".$result['faultstring'];
						$msgreplic .="<br> faultactor... ".$result['faultactor'];
						$msgreplic .="<br>";
						
					} else {
					   
						$msgreplic .=" (Reboque $placareboque ".$result['Descricao'].") ";	
										   
					}
				}						
			}	
			
			
			//****************************//	
			//** S E M I   R E B O Q U E *//
			//****************************//
			
			/*Carreta: cliente, placa,  tipo carroceria,marca,cor,ano*/			
				
			if ($placasemireboque != '' ) {
				
	
				$semireboquemarca = pg_result($res,'semireboquemarca');
				$semireboquemodelo = pg_result($res,'semireboquemodelo');
				$semireboquecor = pg_result($res,'semireboquecor');
				$semireboqueanofabricacao = pg_result($res,'semireboqueanofabricacao');

			
											
				$placasemireboque = substr($placasemireboque, 0, 3).'-'.substr($placasemireboque, 3, 4);

				if ($semireboqueanofabricacao == '') {
					$semireboqueanofabricacao = 0;
				}	

				if ( $semireboquemodelo == '') {
					$semireboquemodelo = 'NAO INFORMADO';
				}	
				
				if ( $semireboquemarca == '') {
					$semireboquemarca = 'NAO INFORMADO';
				}	
				
				if ( $semireboquecor == '') {
					$semireboquecor = 'NAO INFORMADO';
				}	
				
				
				$msgxml  = "
					<oCarretaInsert xsi:type='urn:CarretaInsert' xmlns:urn='urn:IntegraViagensIntf'>
						<CodigoCliente xsi:type='xsd:int'>74181</CodigoCliente>
						<Placa xsi:type='xsd:string'>$placasemireboque</Placa>
						<Id xsi:type='xsd:int'>0</Id>						
						<CodigoTipoCarroceria xsi:type='xsd:int'>21</CodigoTipoCarroceria>
						<AdicionarPesquisa xsi:type='xsd:string'>1</AdicionarPesquisa>
						<DataValidadePesquisa xsi:type='xsd:dateTime'>$validade</DataValidadePesquisa>
						<SenhaPesquisa xsi:type='xsd:string'>$senha</SenhaPesquisa>
						<CNPJ xsi:type='xsd:string'>$cnpjglog</CNPJ>
						<Validacao xsi:type='xsd:string'>$senhaglog</Validacao>						
						<Marca xsi:type='xsd:string'>$semireboquemarca</Marca>
						<Modelo xsi:type='xsd:string'>$semireboquemodelo</Modelo>
						<Cor xsi:type='xsd:string'>$semireboquecor</Cor>
						<AnoFabricacao xsi:type='xsd:int'>$semireboqueanofabricacao</AnoFabricacao>
						<AnoModelo xsi:type='xsd:int'>$semireboqueanofabricacao</AnoModelo>
						<TipoCarroceria xsi:type='xsd:string'>21</TipoCarroceria>						
					 </oCarretaInsert>
				";

				$result = $client->call("InserirCarreta", "$msgxml");	
		 
// replicacao semireboque
//$f = fopen('../_/'.$placasemireboque.' repliacao semireboque.txt', 'w');	fwrite($f,"\n $msgxml" );	fclose($f);

				if ($client->fault) {
						
					//print_r($result);
					
					$msgreplic .="<br> Resposta do WS: ";
					$msgreplic .="<br> faultcode.... ".$result['faultcode'];
					$msgreplic .="<br> faultstring.. ".$result['faultstring'];
					$msgreplic .="<br> faultactor... ".$result['faultactor'];
					$msgreplic .="<br>";
								
				} else {
					
					$error = $client->getError();
					
					if ($error) {
					
						print_r($error);				
						
						$msgreplic .="<br> Resposta do WS: ";
						$msgreplic .="<br> faultcode.... ".$result['faultcode'];
						$msgreplic .="<br> faultstring.. ".$result['faultstring'];
						$msgreplic .="<br> faultactor... ".$result['faultactor'];
						$msgreplic .="<br>";
						
					} else {
					   
						$msgreplic .=" (semireboque $placasemireboque ".$result['Descricao'].") ";	
										   
					}
				}						
			}	
				
			//****************************//	
			//** T E R C E I R O    R E B O Q U E *//
			//****************************//
			
			/*Carreta: cliente, placa,  tipo carroceria,marca,cor,ano*/			
				
			if ($placaterceiroreboque != '' ) {
				
				

				$terceiroreboquemarca = pg_result($res,'terceiroreboquemarca');
				$terceiroreboquemodelo = pg_result($res,'terceiroreboquemodelo');
				$terceiroreboquecor = pg_result($res,'terceiroreboquecor');
				$terceiroreboqueanofabricacao = pg_result($res,'terceiroreboqueanofabricacao');
						
											
				$placaterceiroreboque = substr($placaterceiroreboque, 0, 3).'-'.substr($placaterceiroreboque, 3, 4);


				if ($terceiroreboqueanofabricacao == '') {
					$terceiroreboqueanofabricacao = 0;
				}	


				if ( $terceiroreboquemodelo == '') {
					$terceiroreboquemodelo = 'NAO INFORMADO';
				}	
				
				if ( $terceiroreboquemarca == '') {
					$terceiroreboquemarca = 'NAO INFORMADO';
				}	
				
				if ( $terceiroreboquecor == '') {
					$terceiroreboquecor = 'NAO INFORMADO';
				}	
				
				$msgxml  = "
					<oCarretaInsert xsi:type='urn:CarretaInsert' xmlns:urn='urn:IntegraViagensIntf'>
						<CodigoCliente xsi:type='xsd:int'>74181</CodigoCliente>
						<Placa xsi:type='xsd:string'>$placaterceiroreboque</Placa>
						<Id xsi:type='xsd:int'>0</Id>						
						<CodigoTipoCarroceria xsi:type='xsd:int'>21</CodigoTipoCarroceria>
						<AdicionarPesquisa xsi:type='xsd:string'>1</AdicionarPesquisa>
						<DataValidadePesquisa xsi:type='xsd:dateTime'>$validade</DataValidadePesquisa>
						<SenhaPesquisa xsi:type='xsd:string'>$senha</SenhaPesquisa>
						<CNPJ xsi:type='xsd:string'>$cnpjglog</CNPJ>
						<Validacao xsi:type='xsd:string'>$senhaglog</Validacao>						
						<Marca xsi:type='xsd:string'>$terceiroreboquemarca</Marca>
						<Modelo xsi:type='xsd:string'>$terceiroreboquemodelo</Modelo>
						<Cor xsi:type='xsd:string'>$terceiroreboquecor</Cor>
						<AnoFabricacao xsi:type='xsd:int'>$terceiroreboqueanofabricacao</AnoFabricacao>
						<AnoModelo xsi:type='xsd:int'>$terceiroreboqueanofabricacao</AnoModelo>
						<TipoCarroceria xsi:type='xsd:string'>21</TipoCarroceria>						
					 </oCarretaInsert>
				";

				$result = $client->call("InserirCarreta", "$msgxml");	
		 
//replicacao terceiro reboque
//$f = fopen('../0contab/'.$placaterceiroreboque.' repliacao terceiroreboque.txt', 'w');	fwrite($f,"\n $msgxml" );	fclose($f);

				if ($client->fault) {
						
					//print_r($result);
					
					$msgreplic .="<br> Resposta do WS: ";
					$msgreplic .="<br> faultcode.... ".$result['faultcode'];
					$msgreplic .="<br> faultstring.. ".$result['faultstring'];
					$msgreplic .="<br> faultactor... ".$result['faultactor'];
					$msgreplic .="<br>";
								
				} else {
					
					$error = $client->getError();
					
					if ($error) {
					
						print_r($error);				
						
						$msgreplic .="<br> Resposta do WS: ";
						$msgreplic .="<br> faultcode.... ".$result['faultcode'];
						$msgreplic .="<br> faultstring.. ".$result['faultstring'];
						$msgreplic .="<br> faultactor... ".$result['faultactor'];
						$msgreplic .="<br>";
						
					} else {
					   
						$msgreplic .=" (terceiroreboque $placaterceiroreboque ".$result['Descricao'].") ";	
										   
					}
				}						
			
			}
			
			// envia motorista
			
			if ($cpfcnpj != '' ) {
			
			
		

	$msgxml  = "
	  <oMotoristaInsert xsi:type='urn:MotoristaInsert' xmlns:urn='urn:IntegraViagensIntf'>
				<CodigoCliente xsi:type='xsd:int'>74181</CodigoCliente>
				<Nome xsi:type='xsd:string'>$nomepessoa</Nome>
				<CPF xsi:type='xsd:string'>$cpfcnpj</CPF>
				<RG xsi:type='xsd:string'></RG>				
				<DataNascimento xsi:type='xsd:dateTime'></DataNascimento>				
				<AdicionarPesquisa xsi:type='xsd:string'>1</AdicionarPesquisa>
				<DataValidadePesquisa xsi:type='xsd:dateTime'>$validade</DataValidadePesquisa>
				<SenhaPesquisa xsi:type='xsd:string'>$senha</SenhaPesquisa>
				<CNPJ xsi:type='xsd:string'>$cnpjglog</CNPJ>
				<Telefone xsi:type='xsd:string'>$fone</Telefone>
				<Celular xsi:type='xsd:string'>$celular</Celular>
				<Validacao xsi:type='xsd:string'>$senhaglog</Validacao>			
				<EnderecoLogradouro xsi:type='xsd:string'>$endereco</EnderecoLogradouro>
			 </oMotoristaInsert>
	";


				$result = $client->call("InserirMotorista", $msgxml	);	
				
//replicacao motorista
//$f = fopen('../_/'.$nomepessoa.' repliacao motorista.txt', 'w'); fwrite($f,"\n $msgxml" );	fclose($f);
					
				if ($client->fault) {
					
					print_r($error);				
						
						$msgreplic .="<br> Resposta do WS: ";
						$msgreplic .="<br> faultcode.... ".$result['faultcode'];
						$msgreplic .="<br> faultstring.. ".$result['faultstring'];
						$msgreplic .="<br> faultactor... ".$result['faultactor'];
						$msgreplic .="<br>";
					
				} else {
					
					$error = $client->getError();
					
					if ($error) {
						
						print_r($error);				
						
						$msgreplic .="<br> Resposta do WS: ";
						$msgreplic .="<br> faultcode.... ".$result['faultcode'];
						$msgreplic .="<br> faultstring.. ".$result['faultstring'];
						$msgreplic .="<br> faultactor... ".$result['faultactor'];
						$msgreplic .="<br>";
						
					}else {
					   
						$msgreplic .=" (Motorista  ".$result['Descricao'].") ";	
		
					}
				}		
			}			
		}

		//	echo "<BR><table width=100><tr><td class=menuiz_botonoff><fieldset><legend> </legend>  <img src='../0bmp/a.png'  width='20' height='20'> <b> Replicacao Grupo: $grupoprincipal </b>$msgreplic  </fieldset></td></tr>";
			
		echo " <meta http-equiv='Content-Type' content='text/html; charset=utf-8'> <img src='../0bmp/a.png'  width='20' height='20'> <b>  Replicacao: $grupoprincipal </b>$msgreplic ";
			
	}	


	
}	

function verificablacklistpessoa($codipessoa,$cpfcnpj) {
	
	$sql = "select codipessoa from tocorrenciapessoa where codipessoa = $codipessoa and bloqueado = 't' ";
	
	$res= pg_exec($sql);
	  						
	if (pg_numrows($res) > 0 )  
		return true;
	else
		return false;	
	
}	


function puxablacklistpessoa($codipessoa,$cpfcnpj) {

	$blacklist = '';
	$a = '';	
	
	$sql = "select motivo,obs,usuariocriacao,datacriacao,bloqueado  from tocorrenciapessoa where codipessoa = $codipessoa ";
	
	$res= pg_exec($sql);
		
	if (pg_numrows($res) > 0 )  {
	
		$a .="
		
			<table cellpadding='3' width='100%'>
					
					";
	
	
		for ($i=0; $i < pg_numrows($res ); $i++) {
 
			$arr=pg_fetch_array($res,$i,PGSQL_ASSOC);
			
			//$a .= "<div class='row'>
			$a .= "<tr>
						
							<td> $arr[datacriacao] $arr[usuariocriacao] </td>
							<td> $arr[motivo] </td>						
							<td> $arr[obs] </td>
						</tr>
							";

					
			if ($arr['bloqueado'] == 't' ) {
			
				$blacklist = "							
					
					<div class='alert alert-danger' role='alert' align=center>
						<br><br><img src='../0bmp/a.png'  width='70' height='70' border=5 ><br><h1> A T E N C A O <br><br>- Este motorista esta em blacklist -  <br><br>  cadastro bloqueado  <br><br> **** Nao liberar **** <br><br><h1>
					</div>";
				
			}			
			
		}
		
		$a .="</table>";
			    
		return $blacklist.$a; 	
	
	}else {
		return ''; 			
	}	
	
	
}	

function verificablacklistcarro($placa,$cpfcnpj) {
	
	$sql = "select placa from tocorrenciacarro where placa = '$placa' and bloqueado = 't' ";
	
	$res= pg_exec($sql);
	  						
	if (pg_numrows($res) > 0 )  
		return true;
	else
		return false;	
	
}	

function puxablacklistcarro($placa,$cpfcnpj) {

	$blacklist = '';
	$a = '';	
	
	$sql = "select motivo,obs,usuariocriacao,datacriacao,bloqueado,cpfcnpjcondutor  from tocorrenciacarro where placa = '$placa' ";
	
	$res= pg_exec($sql);
		
	if (pg_numrows($res) > 0 )  {
	
		$a .="
		
			<meta charset='utf-8'/>
			<div class='panel panel-warning' >
				<div class='panel-heading'>
					<div class='panel-body'>
					
					";
		
		for ($i=0; $i < pg_numrows($res ); $i++) {
 
			$arr=pg_fetch_array($res,$i,PGSQL_ASSOC);
			
			//$a .= "<div class='row'>
			$a .= "<div class='row'>
						
							<div class='col-md-2'> <img src='../0bmp/a.png'  width='15' height='15' border=0 > $arr[datacriacao] $arr[usuariocriacao] </div>
							<div class='col-md-2'> $arr[motivo] </div>						
							<div class='col-md-6'> $arr[obs] </div>						
							<div class='col-md-2'> $arr[cpfcnpjcondutor] </div>
						</div>
							";
						
			if ($arr['bloqueado'] == 't' ) {
			
				$blacklist = "							
					<div class='alert alert-borda' style='border-color: red;' role='alert'>
						<br><br><img src='../0bmp/a.png'  width='70' height='70' border=5 ><br><h1> A T E N C A O <br><br>- Este veiculo esta em blacklist -  <br><br>  cadastro bloqueado  <br><br> **** Nao liberar **** <br><br><h1>
				
				</div>";
				
				
				
			}			
			
		}
		
		$a .="</div>
			</div>
			</div>";
			    
		return $a.$blacklist; 	
	
	}else {
		return ''; 			
	}		
	
}	

function procurordo($cpf,$ufrdoapesquisar) {

	// usuarioenviocivil character varying(30),
	// datenviocivil timestamp without time zone,

	$sql = "select dataentrada,datenviocivil,usuarioenviocivil from tserasa where cpfcnpj = '$cpf' and uf = '$ufrdoapesquisar' ";			
    $resql = pg_exec($sql);	

	if ( pg_numrows($resql) > 0 )  { 
				
		$retorno = "Enviado RDO Civil em ".pg_result($resql,'datenviocivil')." usuairo ".pg_result($resql,'usuarioenviocivil');
		return  $retorno;  
	
	} else 
		return '';
	
}

//$obs = procurordovariasuf($_GET[cpf],$arrparcad['rdoufpacote']);
function procurordovariasuf($cpf,$rdoufpacote,$contaprincipal) {

	// usuarioenviocivil character varying(30),
	// datenviocivil timestamp without time zone,

	$sql = "select dataentrada,datenviocivil,usuarioenviocivil
			from tserasa 
			where contaprincipal = $contaprincipal and
				cpfcnpj = '$cpf' and 
				uf in ('$rdoufpacote') ";			
				
    $resql = pg_exec($sql);	

	if ( pg_numrows($resql) > 0 )  { 
				
		$retorno = "Enviado RDO Civil em ".pg_result($resql,'datenviocivil')." usuairo ".pg_result($resql,'usuarioenviocivil');
		return  $retorno;  
	
	} else 
		return '';
	
}




//cria o botao para os anexos
function criabotaoanexo($resp,$cpfplaca) {
	
	$anexos = '';	
	$cordobotao = '';	
	
	for ($idoc=0; $idoc < pg_numrows($resp); $idoc++) {
				
		$copiadocurl = pg_fetch_result($resp,$idoc,'copiadocurl');
		$tipodoc = pg_fetch_result($resp,$idoc,'tipodoc');
		
		if ( $cpfplaca == 'placa')
			$cpfouplaca = pg_fetch_result($resp,$idoc,'placa');
		else //entao pe cpf
			$cpfouplaca = pg_fetch_result($resp,$idoc,'cpfcnpj');
			
		$quantidade = pg_fetch_result($resp,$idoc,'quantidade');
		$extensao = pg_fetch_result($resp,$idoc,'extensao');
		$diasenvio = pg_fetch_result($resp,$idoc,'tdocdataentradadias');					
		
		//se a data do cadastro for hoje entao coloca "agora"
		if ( $diasenvio == '00') {
			$diasenvio = "Hoje $tipodoc $extensao";	
			$cordobotao = "btn-danger";
		}else{
			$cordobotao = "btn-info";
		}			
						
		// se for upload porta century
		if ($copiadocurl == null) {					
			
			$anexos .= "<a class='btn $cordobotao btn-sm' href='../0irdoc/$tipodoc/$cpfouplaca";	
			if ($quantidade != '') $anexos .= "($quantidade)";
			$anexos .= ".$extensao";	
			$anexos .= "' target='_blank' role='button'> $diasenvio dias $tipodoc$quantidade </a>&nbsp;";
			
		}else{						
						
			$search = "/DOCUMENTOS/";
			if (mb_strpos($copiadocurl, $search) !== false) {
				$anexos .= "<a class='btn $cordobotao btn-sm' href='http://www.noxgr.srv.br/noxwebcliente/Uploads/$copiadocurl' target='_blank' role='button'> $diasenvio dias</a>&nbsp;";
			}else{
				$anexos .= "<a class='btn $cordobotao btn-sm' href='$copiadocurl' target='_blank' role='button'> $diasenvio dias</a>&nbsp;";
				
			}	
										
		}		
		
	}	
	
	return $anexos;
	
}

?>
