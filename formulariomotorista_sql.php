<?php session_start(); 

require_once('../0funcoes/fconectadba.php');


if ( $_GET['sq']	== 'buscapessoa' ) {
	

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
			  numero,
				tpessoa.nacionalidade
		from tpessoa  LEFT OUTER JOIN tpessoafisica ON (tpessoa.codipessoa = tpessoafisica.codipessoa) 
		where cpfcnpj = '$_GET[cpfcnpj]'  ";		
	//obs tem que ser left outer join pq se for cpf cadastrado como prorietario nao grava 
	// tpessoa fisica dai quando for fazer select da zebra.
	
	$resp = pg_exec($sql);

	if ( pg_num_rows($resp) > 0 ){

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
		
		// eu coloco dentro do if pg_num_rows($resp) > 0, pq fora ele fica procunrando a 
		// $arr['codipessoafisica'] que nao existe e o sistema fica lento
		if ( trim($arr['codipessoafisica']) == '') {
			$exibetexto .= "['sqlpessoafisica','insert']]";
		}else{
			$exibetexto .= "['sqlpessoafisica','update']]";	
		}
		
	}else{
		// apaga os campos para nao ficar lixo
		$exibetexto .= "['bairro',''],['numero',''],['codipessoa',''],['cedulacnh',''],['numsegurancacnh',''],['obsficha',''],['email',''],['cep',''],['cidade','Selecione'],['uf','Selecione'],['endereco',''],['nomepessoa',''],['fone',''],['celular',''],['rg',''],['tipopessoa',''],['ufrg','Selecione'],['ufnascimento','Selecione'],['cidadenascimento',''],['dtnascimento',''],['datavalidadecnh',''],['ufcnh','Selecione'],['primeirahabilitacao',''],['categoria',''],['numregistro',''],['renach',''],['nomepai',''],['nomemae',''],['nacionalidade',''],";
  	    $exibetexto .= "['sql','insert'],";	
		$exibetexto .= "['sqlpessoafisica','insert']]";

    }
	
	
	echo $exibetexto;
	
	
} else if ( $_GET['sq'] == 'gravapessoa' ) {

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
	
		
		$auxsqlvalor = ''; 
			
		if ( strlen($_GET['nacionalidade']) != '' ) {	
			$auxsqlvalor .= " ,nacionalidade = '$_GET[nacionalidade]' ";
		}
			
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
				
				$auxsqlvalor
				
			where codipessoa = '$_GET[codipessoa]'  ; ";	
	
		// a verificacao se necessita update ou insert em tpessoa fisica � pq tpessoa pode ter sido
		// cadastrado como proprietario, nete caso nao foi gravado em tpessoafisica.
		// portanto existem casos de gravacao em tpessoa e nao gravado em tpessoafisica.
		
		if ($_GET['sqlpessoafisica'] == 'update' ) { 

			
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
		
		if ( strlen($_GET['nacionalidade']) != '' ) {	
			$nomecampo .= ',nacionalidade';
			$auxsqlvalor .= " ,'$_GET[nacionalidade]' ";
		}

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

} else if ( $_GET['sq']	== 'verificasetemcnh' ) {
							
	if ($_GET['cpf'] != '') {

		$sql = " select * from tdoc where cpfcnpj = '$_GET[cpf]' ";

		$res = pg_exec($sql);

		if ( pg_numrows($res) > 0 ){			

			$arr=pg_fetch_array($res,0,PGSQL_ASSOC);
		
			$pathdocs = "<a href='http://192.168.1.250/0irdoc/$arr[tipodoc]/$arr[cpfcnpj]";

			if ($arr['quantidade'] != '')
				$pathdocs .= "($arr[quantidade])";

			$pathdocs .= ".$arr[extensao]";	
			$pathdocs .= "'  target='_blank'>$arr[tipodoc] $arr[extensao].$arr[extensao]</a> &nbsp;";
				
			echo "<p class='btn btn-primary btn-md' href='#' role='button' onclick=\"window.open('http://192.168.1.250/0irdoc/$arr[tipodoc]/$arr[cpfcnpj].$arr[extensao]','imagem','width=1000,height=700');\">Visualizar cnh</p>";

		} else {
			echo "";	
		}	

	} else {
		echo "";	
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
	
	if ( pg_num_rows($resp) > 0 ){

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
	
	if ( pg_num_rows($resp) > 0 ){

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
			
		if ( pg_num_rows($resprefpessoa) < 1 ){

			$sqlpf = "insert into  treferenciapessoa(codipessoa,codireferencia)
						values('$_GET[codipessoa]','$_GET[codireferencia]')";		
						
			$resposta = pg_exec($sqlpf);

		}
			
		$resp = pg_exec($sql);			
		//$resppf = true;
	}
	
	echo "codigo das referencias <br> $sql <br> $sqlpf <br> $_GET[sql]";
			
}	
	
function fmaxcodipessoa() {

  // pega um novo codi registro para tpessoa
  $sqlmaiorcodigo=pg_exec("
      select (max(codipessoa)+1) as maxcodipessoa
      from tpessoa");

  return pg_fetch_result($sqlmaiorcodigo,'maxcodipessoa');  
}  
	
function fmaxcodireferencia() {

  // pega um novo codi registro para tpessoa
  $sqlmaiorcodigo=pg_exec("
      select (max(codireferencia)+1) as maxcodireferencia
      from treferencia");

  return pg_fetch_result($sqlmaiorcodigo,'maxcodireferencia');  
} 	
	
	
?>