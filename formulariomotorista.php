<?php session_start();

require_once('../0funcoes/faviso2.php');

//se o usuario nao fez login ou o login expirou para a executacao da pagina e emite aviso para o usuario efeturar um novo login
If ( $_SESSION['usuario'] == '' && $_SESSION['senha']  == '') {
	die(faviso2("Seu login expirou","por favor efetue novo  <a href='../0usuario/index.php'>Login</a> !")); 
} 

If ( $_SESSION['nivelativo']  == 0 ) {

    die(faviso2("Seu login nao tem privilegio para acessar esta pagina","por favor contacte o administrador de sistema")); 
	// 0 - inativo
	// 1 - basico ( cliente )
	// 2 - medio ( seguradora )
	// 3 - avancado (century)
	// 4 - avancado (century avancado )
	// 5 - mega usuario (todos os acesos)
}

?>

<!DOCTYPE html>
<html>

<head>
	<title>Cadastro</title>
	<link rel="stylesheet" href="style.css?v=01092023">
</head>

<body>


	<!-- carrega a funcao que inicia o ajax -->
	<script language='javascript' src='../0funcoes/ajaxInit.js'></script>

	<!-- carrega a funcao que valida formularios -->
	<script language='javascript' src='../0funcoes/validaformulario.js?v2'></script>

	<!-- valida cpf -->
	<script language='javascript' src='../0funcoes/fvalidacpf.js'></script>

	<!-- valida cnpj -->
	<script language='javascript' src='../0funcoes/fvalidacnpj.js'></script>

	<!-- carrega a funcao que valida formularios -->
	<script language='javascript' src='../0funcoes/fpopulaconta.js'></script>

	<!-- inclui funcoes para exibir as cidades -->
	<script language="JavaScript" src="../0funcoes/cidade.js"></script>

	<!-- abre a tela de iniciorapido -->
	<link rel='stylesheet' href='../0funcoes/estilo.css'>

	<!-- telapara fazer upload de arquivos de mot e veiculo -->
	<script language='javascript' src='../0funcoes/telauploadmotoristaveiculo.js'></script>
	<!-- esse viewport permite ser responsivo -->
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

	<!-- bootstrap grap icon-->
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">


	<!-- inclui funcoes para exibir cokie -->

	<!-- monta o cabecalho -->
	<?php require_once('../0layout/elastix/cabecelastix.php'); ?>

	<!-- monta o menu -->
	<?php require_once('../0funcoes/menucentury.php'); ?>

	<!-- cria o formulario -->

	<div class="geral">

		<body onload="document.getElementById('cpfcnpj').focus();">

			<!-- esta div mostra a tela abertura rapida //-->
			<div id="divpop"></div>

			<div id='avisos' class='alert alert-danger' role='alert'> <img src="../0bmp/interrogacao1.png" align="absmiddle" border="0" width='30' height='30' /> Sr Cliente, afim de agilizar pesquisas,<b> obrigatorio anexar CNH com QRCODE legível !</b></div>



			<table border="0" align=center width="100%" class='redonda'>

				<tr class="moduleTitle">
					<td class="moduleTitle" valign="middle" colspan=3> <img src='../0bmp/motorista.png' width='25' height='25' border='0' align='absmiddle'>
						<mg src="../0layout/elastix/1x1.gif" align="absmiddle" border="0"> Cadastro de pessoa
					</td>
					<td class="moduleTitle" valign="middle">
						<div id='msgajax'></div>
					</td>
				</tr>
				<!-- cria os campos hidden que serao ultilizados na gravacao dos dados do motorista -->

				<input type='hidden' name='codipessoa' id='codipessoa'>
				<!-- codipessoafisica é neceessario pq eu testo se pessoafisica existe e se eu nao colocar aqui o ajax nao traz os dados pq falta campo -->
				<input type='hidden' name='codipessoafisica' id='codipessoafisica'>
				<input type='hidden' name='sql' id='sql'>
				<input type='hidden' name='sqlpessoafisica' id='sqlpessoafisica'>
				<input type='hidden' name='cidade' id='cidade'>
				<input type='hidden' name='uf' id='uf'>
				<input type='hidden' name='cidadenascimento' id='cidadenascimento'>
				<input type='hidden' name='ufnascimento' id='ufnascimento'>
				<input type='hidden' name='tipopessoa' id='tipopessoa'>
				<input type='hidden' name='aviso' id='aviso'>
				<input type='hidden' name='radio' id='radio'>
				<input type='hidden' name='email' id='email'>
				<input type='hidden' name='contaprincipal' id='contaprincipal' value=<?php echo $_SESSION['contaprincipal'] ?>>
				<input type='hidden' name='cpfcnpjconta' id='cpfcnpjconta' value=<?php echo $_SESSION['cpfcnpjconta'] ?>>
				<input type='hidden' id='importacookie' value=''>

				<?php //cnpj transportador é para o cliente lideransat migracao trafegus 
				?>


				<tr>
					<td>
						<select class='redonda' id="nacionalidade" onchange="apagacampos(this.value)">
							<option value=''>CPF BRA
							<option value='1'>RUC PAR
							<option value='2'>CUIT ARG
							<option value='3'>RUT CHI
							<option value='4'>RUT URU

						</select>
					</td>
					<td>
						<div class='input-group'>
							<span class='input-group-btn'>
								<input class='redonda' value='' type='text' id='cpfcnpj' maxlength="15" size="12" onkeypress="mascara(this,soNumeros)" onchange="buscaajax(this.value)" tabindex=1>
								<a class='btn btn-success btn-md ' href='#' role='button' id='Buscar' onclick='buscaajax()'><i class='fa fa-search'></i></a>
								<a class='btn btn-success btn-md ' href='#' role='button' id='biometriaCadastrar' onclick='register()'><i class='fa fa-fingerprint'> <i class='fa fa-save'></i> </i> </a>
								<a class='btn btn-success btn-md ' href='#' role='button' id='biometriaCadastrar' onclick='consult()'><i class='fa fa-fingerprint'> <i class='fa fa-search'></i> </i> </a>
							</span>
						</div>


					</td>

					<td>Foto </td>
					<td rowspan=4>
						<div id='divfotomotorista'></div>
						<img src='' style="display: none;" id='digital' width='120' height='140' border='0' align='absmiddle'>
						<div id='mensagemdoleitorbiometria'></div>
					</td>
				</tr>
				<tr>
					<td> </td>
					<td></td>
				</tr>

				<tr>
					<td>Nome </td>
					<td><input class='redonda' type='text' id='nomepessoa' size='35' maxlength='60' tabindex=2></td>
				</tr>
				<tr id='trcep'>
					<td>Cep</td>
					<td id='tdbairro'><input class='redonda' type='text' id='cep' size='10' onchange='buscacep(); ' maxlength='10' tabindex=3>
						Bairro <input class='redonda' type='text' id='bairro' size='13' maxlength='30' tabindex=4>
					</td>
				</tr>
				<tr id='trendereco'>
					<td>Endereco </td>
					<td> <input class='redonda' type='text' id='endereco' size='24' maxlength='60' tabindex=5>
						n <input class='redonda' type='text' id='numero' size='3' maxlength='6' tabindex=6>
					</td>
				</tr>
				<tr id='trresidencia'>
					<td>Uf Resid </td>
					<td> <select class='redonda' id="ufaux" tabindex=7></select> </td>
					<td>Cidade </td>
					<td> <select class='redonda' id="cidadeaux" tabindex=8>
							<option value=''>Selecione a UF
						</select></td>
				</tr>
				<tr id="trrg" class='redonda'>
					<td>R.G:</td>
					<td>
						<input class='redonda' type='text' id='rg' size='15' maxlength='14' tabindex=9>
						Uf: <select class='redonda' id='ufrg' tabindex=10></select>
					</td>
				</tr>
					<td>Dt. Nasc.:</td>
					<td>
						<input class='redonda' type='text' id='dtnascimento' size='11' maxlength='10' onkeypress="mascara(this,mascaradata)" tabindex=11>
					</td>
				<tr id='trnascimento'>
					<td>UF Nasc. </td>
					<td> <select class='redonda' id='ufnascimentoaux' tabindex=12> </select></td>
					<td>Cidade </td>
					<td><select class='redonda' id='cidadenascimentoaux' tabindex=13>
							<option value=''>Selecione a UF
						</select></td>
				</tr>
				<tr id='trfiliacao'>
					<td>Pai </td>
					<td> <input type='text' class='redonda' id='nomepai' size='35' maxlength='60' tabindex=14></td>
					<td>Mae </td>
					<td> <input type='text' class='redonda' id='nomemae' size='40' maxlength='60' tabindex=15></td>
				</tr>
				<tr id='trreg'>
					<td>Reg. Cnh </td>
					<td> <input class='redonda' type='text' id='numregistro' size='15' maxlength='14' onkeypress="mascara(this,soNumeros)" tabindex=16> Uf: <select class='redonda' id='ufcnh' tabindex=17></select></td>
					<td>Validade </td>
					<td> <input type='text' class='redonda' id='datavalidadecnh' size='12' onkeypress="mascara(this,mascaradata)" maxlength='10' tabindex=18> Cat. Cnh: <input class='redonda' type='text' id='categoria' size='3' maxlength='3' tabindex=19></td>
				</tr>
				<tr id='trnumseg'>
					<td>Num. Seg.</td>
					<td> <input class='redonda' type='text' id='numsegurancacnh' size='15' maxlength='14' onkeypress="mascara(this,soNumeros)" tabindex=20> <a href='#' onclick="window.open('../0interrisco/numerosegurancacnh.htm');"><img src='../0bmp/info.png' width='20' align='absmiddle' height='20'></a> </td>
					<td>Cedula</td>
					<td> <input class='redonda' type='text' id='cedulacnh' size='15' maxlength='14' onkeypress="mascara(this,soNumeros)" tabindex=21> (Cnh para o estado ES/SE/CE)</td>
				</tr>
				<tr id='trdatacnh'>
					<td>
						<div> Data Cnh </div>
					</td>
					<td> <input class='redonda' type='text' id='primeirahabilitacao' size='12' onkeypress="mascara(this,mascaradata)" maxlength='10' tabindex=22> (primeira habilitacao) </td>
					<td>Fone </td>
					<td> <input class='redonda' type='text' id='fone' size='15' maxlength='15' onkeypress="mascara(this,soNumeros)" onchange="eliminamask('fone');" tabindex=23></td>
				</tr>
				<tr id='trrenach'>
					<td>Renach</td>
					<td> <input class='redonda' type='text' id='renach' size='15' maxlength='15' tabindex=24> </td>
					<td>Celular </td>
					<td> <input class='redonda' type='text' id='celular' size='15' maxlength='15' onkeypress="mascara(this,soNumeros)" onchange="eliminamask('celular');" tabindex=26>


						<?php

						if ($_SESSION['fazerbiometria'] == 't') {

							echo "<div class='btn btn-success btn-sm' id='aaa' onclick='enviarparabiometriafacial()'> 
									<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-whatsapp' viewBox='0 0 16 16'>
									<path d='M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z'/>
									</svg>
									
								Enviar link Rec. Facial</div>";
						}
						?>

					</td>
				</tr>


			</table>
			<div id="trref">
				<table border="0" align='center' width="100%" class='redonda'>
					<tr class="moduleTitle">
						<td class="moduleTitle" valign="middle" colspan='4'>&nbsp;&nbsp;<img src="../0layout/elastix/1x1.gif" align="absmiddle" border="0" />Referencias pessoais / comerciais </td>
						<td class="moduleTitle" valign="middle">
							<div id='msgajaxref'> </div>
							<p class='btn btn-info btn-sd mb-2' onclick=buscareferencias()> <i class='fa fa-search'> Buscar </i> </p>

						</td>
					</tr>

					<tr>
						<td> Fone pes </td>
						<td> Nome </td>
						<td> Afinid./Grau parent. </td>
						<td> Estado </td>
						<td> Cidade </td>
						</td>
						<input type='hidden' id='ref1codireferencia'>
						<input type='hidden' id='ref1cidade'>
						<input type='hidden' id='ref1uf'>
						<input type='hidden' id='ref1sql'>
					<tr>
						<td> <input type='text' id='ref1fone' size='13' maxlength='11' onchange="buscaref1(); " tabindex=29></td>
						<td> <input type='text' id='ref1nome' size='25' maxlength='25' tabindex=30></td>
						<td> <input type='text' id='ref1contato' size='25' maxlength='25' tabindex=31></td>
						<td> <select id='ref1ufaux' tabindex=32> </select></td>
						<td> <select id='ref1cidadeaux' tabindex=33>
								<option value=''>Selecione a UF
							</select></td>
					</tr>

					<input type='hidden' id='ref2codireferencia'>
					<input type='hidden' id='ref2cidade'>
					<input type='hidden' id='ref2uf'>
					<input type='hidden' id='ref2sql'>
					<tr>
						<td> <input type='text' id='ref2fone' size='13' maxlength='11' onchange="buscaref2(); " tabindex=34></td>
						<td> <input type='text' id='ref2nome' size='25' maxlength='25' tabindex=35></td>
						<td> <input type='text' id='ref2contato' size='25' maxlength='25' tabindex=36></td>
						<td> <select id='ref2ufaux' tabindex=37> </select></td>
						<td> <select id='ref2cidadeaux' tabindex=38>
								<option value=''>Selecione a UF
							</select></td>
					</tr>

					<input type='hidden' id='ref3codireferencia'>
					<input type='hidden' id='ref3cidade'>
					<input type='hidden' id='ref3uf'>
					<input type='hidden' id='ref3sql'>
					<tr>
						<td> <input type='text' id='ref3fone' size='13' maxlength='11' onchange="buscaref3();" tabindex=39></td>
						<td> <input type='text' id='ref3nome' size='25' maxlength='25' tabindex=40></td>
						<td> <input type='text' id='ref3contato' size='25' maxlength='25' tabindex=41></td>
						<td> <select id='ref3ufaux' tabindex=42> </select></td>
						<td> <select id='ref3cidadeaux' tabindex=43>
								<option value=''>Selecione a UF
							</select></td>
					</tr>

					<tr>
						<td> Fone com </td>
						<td> Nome empresa </td>
						<td> Nome contato </td>
						<td> Estado </td>
						<td> Cidade </td>
						</td>

						<input type='hidden' id='ref4codireferencia'>
						<input type='hidden' id='ref4cidade'>
						<input type='hidden' id='ref4uf'>
						<input type='hidden' id='ref4sql'>
					<tr>
						<td> <input type='text' id='ref4fone' size='13' maxlength='11' onchange="buscaref4();" tabindex=44></td>
						<td> <input type='text' id='ref4nome' size='25' maxlength='25' tabindex=45></td>
						<td> <input type='text' id='ref4contato' size='25' maxlength='25' tabindex=46 '></td>	
						<td> <select id=' ref4ufaux' tabindex=47> </select></td>
						<td> <select id='ref4cidadeaux' tabindex=48>
								<option value=''>Selecione a UF
							</select></td>
					</tr>

					<input type='hidden' id='ref5codireferencia'>
					<input type='hidden' id='ref5cidade'>
					<input type='hidden' id='ref5uf'>
					<input type='hidden' id='ref5sql'>
					<tr>
						<td> <input type='text' id='ref5fone' size='13' maxlength='11' onchange="buscaref5();" tabindex=49></td>
						<td> <input type='text' id='ref5nome' size='25' maxlength='25' tabindex=50></td>
						<td> <input type='text' id='ref5contato' size='25' maxlength='25' tabindex=51></td>
						<td> <select id='ref5ufaux' tabindex=52> </select></td>
						<td> <select id='ref5cidadeaux' tabindex=53>
								<option value=''>Selecione a UF
							</select></td>
					</tr>

					<input type='hidden' id='ref6codireferencia'>
					<input type='hidden' id='ref6cidade'>
					<input type='hidden' id='ref6uf'>
					<input type='hidden' id='ref6sql'>
					<tr>
						<td> <input type='text' id='ref6fone' size='13' maxlength='11' onchange="buscaref6(); " tabindex=54></td>
						<td> <input type='text' id='ref6nome' size='25' maxlength='25' tabindex=55></td>
						<td> <input type='text' id='ref6contato' size='25' maxlength='25' tabindex=56></td>
						<td> <select id='ref6ufaux' tabindex=57> </select></td>
						<td> <select id='ref6cidadeaux' tabindex=58>
								<option value=''>Selecione a UF
							</select></td>
					</tr>

				</table>

				<table border="0" align=center width=100%>
					<tr class="moduleTitle">
						<td class="moduleTitle" valign="middle" colspan='4'>&nbsp;&nbsp;<img src="../0layout/elastix/1x1.gif" align="absmiddle" border="0" />Observacoes da ficha: </td>
					</tr>
					<tr>
						<td><input type='text' id='obsficha' size='100' maxlength='130' tabindex=59></td>
					</tr>
				</table>

				<hr>

				<div id='botao'></div>

				<!-- cria a div que exibe avisos do formulario -->

				<div id='respostaajax'></div>
			</div>

		</body>
	</div>
	<?php echo "<div class='card-header'> Versao 1.2.6396 </div>" ?>

</body>

</html>

<script>
	function apagacampos(valor) {
		// IDs que serão escondidos:
		const mostrar = [
			"trcep",
			"trendereco",
			"trresidencia",
			"trnascimento",
			"trrg",
			"trreg",
			"trnumseg",
			"trdatacnh",
			"trrenach",
			"trref"
		];

		if (valor === "") {
			// Quando brasileiro mostra do array 'mostrar': 
			mostrar.forEach(id => {
				document.getElementById(id).hidden = false;
			});
		} else {
			// Quando estrangeiro esconde os campos do array 'mostrar'.
			mostrar.forEach(id => {
				document.getElementById(id).hidden = true;
			});
		}
	}
</script>


<script language='JavaScript' type='text/javascript' charset='utf-8'>
	function MM_openBrWindow(theURL, winName, features) {
		window.open(theURL, winName, features);
	}



	function enviarparabiometriafacial() {

		var cpfcnpj = document.getElementById('cpfcnpj').value;

		//verifico se tem Cnh

		//define a varivel ajax como a conexao ajax  que foi feita 
		vajaxmot = ajaxInit();

		//abre uma instancia ajax ultiliznado get e mandando para a funcao funajax.php
		vajaxmot.open('get', 'formulariomotorista_sql.php?&sq=verificasetemcnh&cpf=' + cpfcnpj);


		//a cada vez que o estado mudar chama a funcao preencheoscampos
		vajaxmot.onreadystatechange = function() {

			if (vajaxmot.readyState == 4 && vajaxmot.status == 200) {



				resposta = vajaxmot.responseText;

				//alert(resposta);			

				if (resposta == '') {

					alert('Sr cliente, verifiquei no sistema que ainda não foi feito upload da cnh digital com qrcode legivel, favor anexar para validar a biometria facial');
					return false;

				} else {

					var nome = document.getElementById('nomepessoa').value;
					var codipessoa = document.getElementById('codipessoa').value;
					var celular = document.getElementById('celular').value;
					var usuario = "<?php echo $_SESSION['usuario'] ?>";
					var contaprincipal = "<?php echo $_SESSION['contaprincipal'] ?>";
					var conta = "<?php echo $_SESSION['conta'] ?>";
					var dtnascimento = document.getElementById('dtnascimento').value;
					var nomeconta = "<?php echo $_SESSION['nomeconta'] ?>";

					var maillogo = "<?php echo $_SESSION['maillogo'] ?>";
					var token = "<?php
									//crio um codigo de protocolo;
									$t = microtime(true);
									$micro = sprintf("%06d", ($t - floor($t)) * 1000000);
									$d = new DateTime(date('Y-m-d H:i:s.' . $micro, $t));
									$senhaprotocolo = $d->format("ymdHisu");

									echo $senhaprotocolo
									?>";

					if (celular.length < 11) {

						alert("Sr. cliente por favor informe um fone celular válido !");

					} else if (cpfcnpj.length < 11) {

						alert("Sr. cliente por favor informe um CPF  válido !");

					} else if (codipessoa.length < 3) {

						alert("Sr. cliente por favor informe um codipessoa válido !");

					} else {

						//verifico qual empresa é para direcionar para o dominio correto
						if (maillogo == 'krona') {

							host = 'https://maxxicadastro.com.br/whats/index.php?&celular=' + celular + '&nome=' + nome + '&cpfcnpj=' + cpfcnpj + '&codipessoa=' + codipessoa + '&token=' + token + '&dtnascimento=' + dtnascimento + '&usuario=' + usuario + '&contaprincipal=' + contaprincipal + '&conta=' + conta + '&nomeconta=' + nomeconta + '&maillogo=krona';

						} else if (maillogo == 'a2seg') {

							host = 'https://a2cadastro.com.br/whats/index.php?&celular=' + celular + '&nome=' + nome + '&cpfcnpj=' + cpfcnpj + '&codipessoa=' + codipessoa + '&token=' + token + '&dtnascimento=' + dtnascimento + '&usuario=' + usuario + '&contaprincipal=' + contaprincipal + '&conta=' + conta + '&nomeconta=' + nomeconta + '&maillogo=a2seg';

						} else {

							host = 'https://maxxicadastro.com.br/whats/index.php?&celular=' + celular + '&nome=' + nome + '&cpfcnpj=' + cpfcnpj + '&codipessoa=' + codipessoa + '&maillogo=' + maillogo + '&token=' + token + '&dtnascimento=' + dtnascimento + '&usuario=' + usuario + '&contaprincipal=' + contaprincipal + '&conta=' + conta + '&nomeconta=' + nomeconta;

						}

						window.open(host, '', 'width=700,height=420,left=50,top=50');

					}

					return true; //	alert('Sr. cliente, cnh encontrada'+resposta);

				}

			}
		}

		//envia a solicitacao ajax
		vajaxmot.send(null);




		/*if ( temcnh(cpfcnpj)  ) {
		
			alert('tem cnh');	
		
		}	else {
			
				alert(' nao tem cnh');	
		}		
		*/





	}



	//para abrir o java da biometria
	//windows\systen32\cmd.exe -java -jar Bioreader.1.3-SNAPSHOT.jar



	var ws;



	// mensagem de colocar o dedo no leitor
	document.getElementById("mensagemdoleitorbiometria").innerHTML = "";




	$(document).ready(function() {
		connect();
	});

	function connect() {

		console.log('Conectando ao WebSocket');

		ws = new WebSocket('ws://192.168.1.250:8083/user');
		ws.onmessage = function(data) {

			var vetor = JSON.parse(data.data);
			console.log(vetor)

			if (vetor[0] == "Consulta") {
				if (vetor[3] == "true") {
					$('#nomepessoa').val(vetor[2].trim().replace("\"", "").replace("\"", ""));
					$("#cpfcnpj").val(vetor[1].trim().replace("\"", "").replace("\"", ""));


					// a linha de baixo, imprime a digital ou seja, se quiser adicionar no layout só descomentar embaixo
					$('#digital').attr('src', 'data:image/png;base64,' + vetor[4]);
					$('#digital').removeAttr('style');


					// mensagem de colocar o dedo no leitor
					document.getElementById("mensagemdoleitorbiometria").innerHTML = "";


					//se achou cpf
					if ($("#cpfcnpj").val() != '') {

						buscaajax();

					}


				} else {
					// aqui voce trabalha o que vai acontecer no DOM quando a digital n for reconhecida ou o dedo tiver sujo
					alert('Digital não cadastrada em nosso banco de dados!');
				}
			}
			if (vetor[0] == "Registro" && vetor[1] == "true") {
				alert('Digital cadastrada!');
			}
		}

	}
	// essa função desconecta o websocket, n é necessário a menos que voce esteja tendo problema de lentidao na leitura
	// ai voce chama quando finalizar uma consulta ou registro
	function disconnect() {
		if (ws != null) {
			ws.close();
		}
	}
	// nao mexer nessa parte debaixo
	function sendData() {

		var data = JSON.stringify({
			'user': $("#user").val()
		})
		ws.send(data);

	}


	//botao que faz a consulta no leitor digital
	function consult() {

		//informa para por a digital no leitor 
		document.getElementById("mensagemdoleitorbiometria").innerHTML = "<div class='alert alert-info' role='alert'>Favor inserir digital no dispositivo para leitura !</div> ";
		//document.getElementById("mensagemdoleitorbiometria").style.display = "block";

		// aqui voce trabalha o q vai alterar no DOM antes de fazer a consulta
		var data = JSON.stringify({
			'consult': true
		});
		ws.send(data);

	}

	//funcao que faz o registro da biometria
	function register() {
		// aqui voce trabalha o q vai alterar no DOM antes de fazer o registro
		var nome = $("#nomepessoa").val();
		var cpf = $("#cpfcnpj").val();

		if (cpf == '') {

			//informa para por a digital no leitor 
			document.getElementById("mensagemdoleitorbiometria").innerHTML = "<div class='alert alert-danger' role='alert'>Por gentileza inserir um CPF para vincular digital capturada ! </div> ";
			return false;

		} else {


			//0funcoes/fvalidacpf.js
			if (!valida_cpf(cpf, 'CPF/CNPJ')) {

				document.getElementById("mensagemdoleitorbiometria").innerHTML = "<div class='alert alert-danger' role='alert'>Favor verificar se cpf informado é valido ! </div> ";
				return false;

			}

			//informa para por a digital no leitor 
			document.getElementById("mensagemdoleitorbiometria").innerHTML = "<div class='alert alert-info' role='alert'>Por gentileza inserir digital no dispositivo para Gravação!</div> ";


		}


		var data = JSON.stringify({
			'consult': false,
			'nome': nome,
			'cpf': cpf
		});

		ws.send(data);

	}

	function biometriacadastro() {

		vajax = ajaxInit();

		vajax.open('get', 'biometria.php?&sq=biometriacadastro&cpfcnpj=' + document.getElementById('cpfcnpj').value + '&nomepessoa=' + document.getElementById('nomepessoa').value);

		//alert('   \n Enviando formulario \n Cpf: '+document.getElementById('cpfcnpj').value+'\n Nome: '+document.getElementById('nomepessoa').value);			

		document.getElementById('msgajax').innerHTML = 'Solicitando Cadastro Cpf: ' + document.getElementById('cpfcnpj').value + ' Nome: ' + document.getElementById('nomepessoa').value;

		//a cada vez que o estado mudar chama a funcao preencheoscampos
		vajax.onreadystatechange = function() {

			if (vajax.readyState == 1) {
				//document.getElementById('msgajax').innerHTML = 'Buscando cep...';
			}

			if (vajax.readyState == 4 && vajax.status == 200) {

				document.getElementById('msgajax').innerHTML = 'Pronto!';

				//define a variavel resposta como a resposta trazida pelo ajax
				resposta = vajax.responseText;

				document.getElementById('msgajax').innerHTML = 'Sucesso Cadastro:  ' + resposta;

			}
		}

		//envia a solicitacao ajax
		vajax.send(null);

	}

	function biometriaconsulta() {

		vajax = ajaxInit();

		vajax.open('get', 'biometria.php?&sq=biometriaconsulta&cpfcnpj=' + document.getElementById('cpfcnpj').value + '&nomepessoa=' + document.getElementById('nomepessoa').value);

		document.getElementById('msgajax').innerHTML = 'Solicitando reconhecimento Cpf: ' + document.getElementById('cpfcnpj').value + ' Nome: ' + document.getElementById('nomepessoa').value;

		//a cada vez que o estado mudar chama a funcao preencheoscampos
		vajax.onreadystatechange = function() {

			// Exibe a mensagem "Aguarde..." enquanto carrega
			if (vajax.readyState == 1) {
				//document.getElementById('msgajax').innerHTML = 'Buscando cep...';
			}

			if (vajax.readyState == 4 && vajax.status == 200) {

				document.getElementById('msgajax').innerHTML = 'Pronto!';

				//define a variavel resposta como a resposta trazida pelo ajax
				resposta = vajax.responseText;

				document.getElementById('msgajax').innerHTML = 'Reconhecimento Concluido:  ' + resposta;
				// faco a solicitacao via java

				//alert('  indo para o getcpf');			

				//solicito o cpf para o ajax
				getCpf();

				//alert('  passou getcpf');			

			}
		}

		//envia a solicitacao ajax
		vajax.send(null);

	}


	//busca o total de referencias para deletar
	function buscareferencias() {


		window.open("listareferencia_sql.php?&sq=listareferencia&codipessoa=" + document.getElementById('codipessoa').value, "", "width=900,height=600,location=no");


	}





	function upload() {

		// funcao uploaddocumentos() está na pasta /funcoes/telauploadmotoristaveiculo.js e incluido em /0interrisco/registrospendentes.php
		uploaddocumentos(document.getElementById('cpfcnpj').value, document.getElementById('codipessoa').value, '', '');

	}

	function eliminamask($campo) {

		document.getElementById($campo).value = document.getElementById($campo).value.replace('-', '');
		document.getElementById($campo).value = document.getElementById($campo).value.replace(' ', '');
		document.getElementById($campo).value = document.getElementById($campo).value.replace(' ', '');
		document.getElementById($campo).value = document.getElementById($campo).value.replace(' ', '');
		document.getElementById($campo).value = document.getElementById($campo).value.replace('(', '');
		document.getElementById($campo).value = document.getElementById($campo).value.replace(')', '');
		document.getElementById($campo).value = document.getElementById($campo).value.trim().toUpperCase();

	}

	//lecookie esta na funcao cokie.js
	//var temcookie = lecookie('cpfcnpj');

	// esta funcao foi declarada em 0funcoes/validaformulario.js
	fpopulauf('ufrg', 'Selecione');
	fpopulauf('ufcnh', 'Selecione');


	function eliminacookie() {

	}

	//0funcoes/cidade.js
	new dgCidadesEstados({
		cidade: document.getElementById('cidadeaux'),
		estado: document.getElementById('ufaux')
	})

	//0funcoes/cidade.js
	new dgCidadesEstados({
		cidade: document.getElementById('cidadenascimentoaux'),
		estado: document.getElementById('ufnascimentoaux')
	})




	//0funcoes/cidade.js
	new dgCidadesEstados({
		cidade: document.getElementById('ref1cidadeaux'),
		estado: document.getElementById('ref1ufaux')
	})

	//0funcoes/cidade.js
	new dgCidadesEstados({
		cidade: document.getElementById('ref2cidadeaux'),
		estado: document.getElementById('ref2ufaux')
	})

	//0funcoes/cidade.js
	new dgCidadesEstados({
		cidade: document.getElementById('ref3cidadeaux'),
		estado: document.getElementById('ref3ufaux')
	})
	//0funcoes/cidade.js
	new dgCidadesEstados({
		cidade: document.getElementById('ref4cidadeaux'),
		estado: document.getElementById('ref4ufaux')
	})
	//0funcoes/cidade.js
	new dgCidadesEstados({
		cidade: document.getElementById('ref5cidadeaux'),
		estado: document.getElementById('ref5ufaux')
	})
	//0funcoes/cidade.js
	new dgCidadesEstados({
		cidade: document.getElementById('ref6cidadeaux'),
		estado: document.getElementById('ref6ufaux')
	})


	//inicia a funcao que busca os dados do motorista no banco de dados ultilizando ajax
	function buscaajax(parchave) { //deve ser passado o nome do campo em que esta o cpf onblur='(this)';

		//mensagem do leitor biometrico
		document.getElementById("mensagemdoleitorbiometria").innerHTML = '';

		var $cpfcnpj = document.getElementById('cpfcnpj').value;

		document.getElementById('avisos').innerHTML = "";


		$cpfcnpj = $cpfcnpj.replace(/\D/g, "");


		document.getElementById('cpfcnpj').value = $cpfcnpj;

		zeracamposref('ref1');
		zeracamposref('ref2');
		zeracamposref('ref3');
		zeracamposref('ref4');
		zeracamposref('ref5');
		zeracamposref('ref6');

		//alert($cpfcnpj );
		// verifica se o usuario digitou a conta
		if ($cpfcnpj.length > 0) {




			//0funcoes/fvalidacpf.js

			if (document.getElementById('nacionalidade').value == '') {

				if (!valida_cpf($cpfcnpj, 'CPF/CNPJ')) {
					return false;
				}

			}


			//define a varivel ajax como a conexao ajax  que foi feita 
			vajaxmot = ajaxInit();

			//abre uma instancia ajax ultiliznado get e mandando para a funcao funajax.php
			vajaxmot.open('get', 'formulariomotorista_sql.php?&sq=buscapessoa&cpfcnpj=' + $cpfcnpj);


			//a cada vez que o estado mudar chama a funcao preencheoscampos
			vajaxmot.onreadystatechange = function() {

				// Exibe a mensagem "Aguarde..." enquanto carrega
				if (vajaxmot.readyState == 1) {
					document.getElementById('msgajax').innerHTML = 'Buscando...';
				}

				if (vajaxmot.readyState == 4 && vajaxmot.status == 200) {

					document.getElementById('msgajax').innerHTML = 'Pronto!';

					resposta = vajaxmot.responseText;

					//alert('   '+resposta);				

					arrayresp = window.eval(resposta);

					//document.getElementById('avisos').innerHTML = resposta;

					if (arrayresp != null) {

						for (var p = 0; p < arrayresp.length; p++) {

							//prorblema: quando busca e nao retorna dados, a nacionalidade vai para Brasil
							//solucao: se ja tem selecionado uma nacionalidade, nao altera o campo para nulo que é o retorno do banco de dados
							//logica: se a nacionalidade for diferente de nulo, significa que é extrangeiro
							//      	emtao se extrangeiro se trouxer dados significa que existe um extrangeiro
							//			visualize os dados 
							//			senao nao visualize os dados para nao alterar o campo nacionalidde

							//se a nacionalidade for diferente de nulo, significa que é extrangeiro
							if (document.getElementById('nacionalidade').value != '') {

								// se for extrangeiro verifique se nao ta cadastrado no baco
								if (arrayresp[p][1] == '') {

									// se nao trouxe dados nao altero o campo nacionalidade
									if (arrayresp[p][0] != 'nacionalidade') {

										//vai ser tudo nulo
										document.getElementById(arrayresp[p][0]).value = arrayresp[p][1];

									}

								}

							} else {

								document.getElementById(arrayresp[p][0]).value = arrayresp[p][1];

							}
							// Se o campo nacionalidade estiver diferente de "", os campos 'cep', 'bairro', ''

							/*if (arrayresp[p][0] == 'copiadoc') {	
							
								$doc ="";	
								
								if ( strlen ($arr['copiadoc'] ) > 2 ) {
													
									$pieces = explode(";", arrayresp[p][1]);
									foreach($pieces as $arq){				
										$doc +="<a href='../0uploaddoc/$arq' target='_blank'>$arq</a> ";
									}		
									
								}
							}
							*/

						}

						new dgCidadesEstados({
							cidade: document.getElementById('cidadeaux'),
							estado: document.getElementById('ufaux'),
							estadoVal: document.getElementById('uf').value,
							cidadeVal: document.getElementById('cidade').value
						})

						new dgCidadesEstados({
							cidade: document.getElementById('cidadenascimentoaux'),
							estado: document.getElementById('ufnascimentoaux'),
							estadoVal: document.getElementById('ufnascimento').value,
							cidadeVal: document.getElementById('cidadenascimento').value
						})



						//lecookie esta na funcao validaformulario.js
						//document.getElementById('ref1fone').value = lecookie('ref1fone');
						if (document.getElementById('ref1fone').value != '')
							buscaref1();

						//document.getElementById('ref2fone').value = lecookie('ref2fone');
						if (document.getElementById('ref2fone').value != '')
							buscaref2();

						//document.getElementById('ref3fone').value = lecookie('ref3fone');
						if (document.getElementById('ref3fone').value != '')
							buscaref3();

						//document.getElementById('ref4fone').value = lecookie('ref4fone');
						if (document.getElementById('ref4fone').value != '')
							buscaref4();

						//document.getElementById('ref5fone').value = lecookie('ref51fone');
						if (document.getElementById('ref5fone').value != '')
							buscaref5();

						//document.getElementById('ref6fone').value = lecookie('ref6fone');
						if (document.getElementById('ref6fone').value != '')
							buscaref6();

						document.getElementById('msgajax').innerHTML = '';
						document.getElementById('divfotomotorista').innerHTML = "<img src='../0foto/" + document.getElementById('cpfcnpj').value + "' width='114' height='152'  border='0' align='absmiddle'>   <a href='#' onClick=\"window.open('../0foto/foto.php?&cpfcnpj=" + document.getElementById('cpfcnpj').value + "', '', 'width=900,height=500')\" > <img src='../0bmp/foto.png' width='22' height='18'  border='0' align='absmiddle'> Inserir foto</a> ";

						//document.getElementById('avisos').innerHTML =  $doc;

					}



					tlb = "<div class='row'>";
					tlb += "	<div class='col-md-4'>	</div>";
					tlb += "	<div class='col-md-4'>";

					if (document.getElementById('codipessoa').value != '') {


						tlb += "	<button type='submit' class='btn btn-success upload btn-lg' onclick=\"window.open('../0irupload/uploadtela.php?&chavemotplaca=Condutor&chave=" + document.getElementById('cpfcnpj').value + "&codipessoa=" + document.getElementById('codipessoa').value + "','', 'width=500,height=400,location=no');\"> <i class='fa fa-cloud-upload-alt fa-lg' ></i> Upload Documentos </button>  ";


					}

					tlb += "	<button type='submit' class='btn btn-success upload btn-lg' onclick=\"salva()\"> Salvar dados</button>  ";
					tlb += "</div>";
					tlb += "<div class='col-md-4'>	</div>";
					tlb += "</div>";

					document.getElementById('botao').innerHTML = tlb;


				}
			}

			//envia a solicitacao ajax
			vajaxmot.send(null);

		}
	}

	function buscacep() {

		document.getElementById('avisos').innerHTML = "";

		document.getElementById('cep').value = document.getElementById('cep').value.replace(/\D/g, "");

		if (document.getElementById('cep').value.length == 8) {


			vajax = ajaxInit();

			vajax.open('get', '../0irfun/buscacep_sql.php?&sq=buscacep&cep=' + document.getElementById('cep').value);

			vajax.onreadystatechange = function() {

				if (vajax.readyState == 1) {
					document.getElementById('msgajax').innerHTML = 'Buscando cep...';
				}

				if (vajax.readyState == 4 && vajax.status == 200) {


					resposta = vajax.responseText;

					arrayresp = window.eval(resposta);

					if (arrayresp != null) {

						for (var p = 0; p < arrayresp.length; p++) {

							document.getElementById(arrayresp[p][0]).value = arrayresp[p][1];

						}

						new dgCidadesEstados({
							cidade: document.getElementById('cidadeaux'),
							estado: document.getElementById('ufaux'),
							estadoVal: document.getElementById('uf').value,
							cidadeVal: document.getElementById('cidade').value
						})
					}

				}
			}

			//envia a solicitacao ajax
			vajax.send(null);
			document.getElementById('endereco').value.setfocus();

		}
	}

	function validaref(ref) {


		// validacao de campos digitados pelo usuario
		var vvalidacampo = true;

		document.getElementById('fone').value = document.getElementById('fone').value.replace(/\D/g, "");

		document.getElementById(ref + 'nome').value = document.getElementById(ref + 'nome').value.trim().toUpperCase();
		document.getElementById(ref + 'contato').value = document.getElementById(ref + 'contato').value.trim().toUpperCase();

		//alert('valida '+ref+'fone');	

		if (document.getElementById(ref + 'fone').value.length > 0) {


			if (validaformulario_sonumero(document.getElementById(ref + 'fone').value, 10, 12, ref + 'fone')) {
				document.getElementById(ref + 'fone').style.backgroundColor = '#FFFFFF';
			} else {
				document.getElementById(ref + 'fone').style.backgroundColor = "#EE6363";
				document.getElementById(ref + 'fone').focus();
				vvalidacampo = false;
			}

			if (validaformulario_alfa(document.getElementById(ref + 'nome').value, 2, 60, ref + 'nome')) {
				document.getElementById(ref + 'nome').style.backgroundColor = '#FFFFFF';
			} else {
				document.getElementById(ref + 'nome').style.backgroundColor = "#EE6363";
				document.getElementById(ref + 'nome').focus();
				vvalidacampo = false;
			}


			if (validaformulario_alfa(document.getElementById(ref + 'cidadeaux').value, 2, 60, ref + 'cidade')) {
				document.getElementById(ref + 'cidadeaux').style.backgroundColor = '#FFFFFF';
			} else {
				document.getElementById(ref + 'cidadeaux').style.backgroundColor = "#EE6363";
				document.getElementById(ref + 'cidadeaux').focus();
				vvalidacampo = false;
			}


			if (validaformulario_alfa(document.getElementById(ref + 'ufaux').value, 2, 2, ref + 'Uf')) {
				document.getElementById(ref + 'ufaux').style.backgroundColor = '#FFFFFF';
			} else {
				document.getElementById(ref + 'ufaux').style.backgroundColor = "#EE6363";
				document.getElementById(ref + 'ufaux').focus();
				vvalidacampo = false;
			}

			if (validaformulario_letra(document.getElementById(ref + 'contato').value, 1, 90, ref + 'contato')) {
				document.getElementById(ref + 'contato').style.backgroundColor = '#FFFFFF';
			} else {
				document.getElementById(ref + 'contato').style.backgroundColor = "#EE6363";
				document.getElementById(ref + 'contato').focus();
				vvalidacampo = false;
			}



			//../0funcoes/validaformulario.js 
			if (!validaformulario_sonumero(document.getElementById('codipessoa').value, 0, 15, 'Codi motorista') &&
				!validaformulario_alfa(document.getElementById(ref + 'sql').value, 0, 11, ref + 'sql')) {

				vvalidacampo = false;

			}

		} else {
			vvalidacampo = false;
		}

		return vvalidacampo;
	}

	//inicia a funcao que busca os dados do motorista no banco de dados ultilizando ajax
	function salva() { //deve ser passado o nome do campo em que esta o cpf onblur='(this)';

		if (valida()) {

			//alert('ok');

			//define a varivel ajax como a conexao ajax  que foi feita 
			ajaxsalva = ajaxInit();

			//alert('ok1');
			//abre uma instancia ajax ultiliznado get e mandando para a funcao funajax.php		
			ajaxsalva.open('get', "formulariomotorista_sql.php?&sq=gravapessoa&sql=" + document.getElementById('sql').value + "&cedulacnh=" + document.getElementById('cedulacnh').value + "&sqlpessoafisica=" + document.getElementById('sqlpessoafisica').value + "&codipessoa=" + document.getElementById('codipessoa').value + "&cep=" + document.getElementById('cep').value + "&cidade=" + document.getElementById('cidadeaux').value + "&uf=" + document.getElementById('ufaux').value + "&endereco=" + document.getElementById('endereco').value + "&nomepessoa=" + document.getElementById('nomepessoa').value + "&fone=" + document.getElementById('fone').value + "&celular=" + document.getElementById('celular').value + "&rg=" + document.getElementById('rg').value + "&ufrg=" + document.getElementById('ufrg').value + "&ufnascimento=" + document.getElementById('ufnascimentoaux').value + "&cidadenascimento=" + document.getElementById('cidadenascimentoaux').value + "&dtnascimento=" + document.getElementById('dtnascimento').value + "&datavalidadecnh=" + document.getElementById('datavalidadecnh').value + "&ufcnh=" + document.getElementById('ufcnh').value + "&categoria=" + document.getElementById('categoria').value + "&numregistro=" + document.getElementById('numregistro').value + "&nomepai=" + document.getElementById('nomepai').value + "&nomemae=" + document.getElementById('nomemae').value + "&cpfcnpj=" + document.getElementById('cpfcnpj').value + "&tipopessoa=" + document.getElementById('tipopessoa').value + "&radio=" + document.getElementById('radio').value + "&email=" + document.getElementById('email').value + "&primeirahabilitacao=" + document.getElementById('primeirahabilitacao').value + "&renach=" + document.getElementById('renach').value + "&numsegurancacnh=" + document.getElementById('numsegurancacnh').value + "&obsficha=" + document.getElementById('obsficha').value + "&bairro=" + document.getElementById('bairro').value + "&numero=" + document.getElementById('numero').value + "&nacionalidade=" + document.getElementById('nacionalidade').value);
			//alert('2 '+document.getElementById('obsficha').value );
			//alert('ok2');		
			//a cada vez que o estado mudar chama a funcao preencheoscampos
			ajaxsalva.onreadystatechange = function() {

				//alert('ok3');		
				//estados do ajax
				//1. Sendo enviado;
				//2. Processando;
				//3. Armazenando;
				//4. Pronto. 	

				if (ajaxsalva.readyState == 4) {

					//alert('entoru');

					//define a variavel resposta como a resposta trazida pelo ajax
					resposta = ajaxsalva.responseText;

					//alert(resposta);

					//transforma a resposta trazida do banco em um array
					// este window.eval so serve para trazer array
					arrayresp = window.eval(resposta);

					//alert('teste');

					//se trouxe algum valor do banco de dados
					if (arrayresp != null) {

						// pega o codipessoa
						document.getElementById(arrayresp[0][0]).value = arrayresp[0][1].trim();

						//salva referencias		
						if (validaref('ref1')) {

							//alert('positivo ref1');
							salvareferencias(document.getElementById('codipessoa').value, 'ref1');
						}

						if (validaref('ref2')) {
							salvareferencias(document.getElementById('codipessoa').value, 'ref2');
						}

						if (validaref('ref3')) {
							salvareferencias(document.getElementById('codipessoa').value, 'ref3');
						}

						if (validaref('ref4')) {
							salvareferencias(document.getElementById('codipessoa').value, 'ref4');
						}

						if (validaref('ref5')) {
							salvareferencias(document.getElementById('codipessoa').value, 'ref5');
						}

						if (validaref('ref6')) {
							salvareferencias(document.getElementById('codipessoa').value, 'ref6');
						}

						document.getElementById('cpfcnpj').focus();



						if (arrayresp[0][1].trim() == '') {

							// aqui vai retornar um positivo ou negativo ao lado dos botoes
							document.getElementById('avisos').innerHTML = "<table><tr><td class=menuiz_botonoff><fieldset><legend><b>Mensagem </b></legend>   <img src='../0bmp/neg.png' width='45' height='25'  border='0' align='absmiddle'>  Nao foi possivel efetuar gravacao ! </fieldset></td></tr>";

						} else {

							document.getElementById('avisos').innerHTML = "<table><tr><td class=menuiz_botonoff><fieldset><legend><b>Mensagem </b></legend> <img src='../0bmp/pos.png' width='45' height='25'  border='0' align='absmiddle'> Gravacao OK ! </fieldset></td></tr>";

							//funcao declarada aqui mesmo	
							//eliminacookie();
						}

					}
				}
			}
			//envia a solicitacao ajax
			ajaxsalva.send(null);

			/////////////////////////
			// migracao lideransat //
			/////////////////////////

			//codigo lideransat 871563
			//if (document.getElementById('contaprincipal').value == 871563 ) {
			if (document.getElementById('contaprincipal').value == 871563) {

				//alert('if');

				ajaximporta = ajaxInit();

				//abre uma instancia ajax ultiliznado get e mandando para a funcao funajax.php		
				ajaximporta.open('get', "exportalideransat.php?&cpfcnpjconta=" + document.getElementById('cpfcnpjconta').value + "&cep=" + document.getElementById('cep').value + "&cidade=" + document.getElementById('cidadeaux').value + "&uf=" + document.getElementById('ufaux').value + "&endereco=" + document.getElementById('endereco').value + "&nomepessoa=" + document.getElementById('nomepessoa').value + "&fone=" + document.getElementById('fone').value + "&celular=" + document.getElementById('celular').value + "&rg=" + document.getElementById('rg').value + "&ufrg=" + document.getElementById('ufrg').value + "&ufnascimento=" + document.getElementById('ufnascimentoaux').value + "&cidadenascimento=" + document.getElementById('cidadenascimentoaux').value + "&dtnascimento=" + document.getElementById('dtnascimento').value + "&datavalidadecnh=" + document.getElementById('datavalidadecnh').value + "&ufcnh=" + document.getElementById('ufcnh').value + "&categoria=" + document.getElementById('categoria').value + "&numregistro=" + document.getElementById('numregistro').value + "&nomepai=" + document.getElementById('nomepai').value + "&nomemae=" + document.getElementById('nomemae').value + "&cpfcnpj=" + document.getElementById('cpfcnpj').value + "&tipopessoa=" + document.getElementById('tipopessoa').value + "&radio=" + document.getElementById('radio').value + "&email=" + document.getElementById('email').value + "&primeirahabilitacao=" + document.getElementById('primeirahabilitacao').value + "&renach=" + document.getElementById('renach').value + "&numsegurancacnh=" + document.getElementById('numsegurancacnh').value + "&obsficha=" + document.getElementById('obsficha').value);

				//a cada vez que o estado mudar chama a funcao preencheoscampos
				ajaximporta.onreadystatechange = function() {

					if (ajaximporta.readyState == 4) {

						//define a variavel resposta como a resposta trazida pelo ajax
						resposta = ajaximporta.responseText;

					}
				}
				//envia a solicitacao ajax
				ajaximporta.send(null);

			}
		}
	}

	// zera campos necessario se foi cadatrado e depois um insert, tem que limpar campos
	function zeracampospessoa() {

		document.getElementById('sql').value = '';
		document.getElementById('cep').value = '';
		document.getElementById('codipessoa').value = '';
		document.getElementById('ufaux').value = 'Selecione';
		document.getElementById('cidadeaux').value = 'Selecione';
		document.getElementById('uf').value = 'Selecione';
		document.getElementById('cidade').value = '';
		document.getElementById('bairro').value = '';
		document.getElementById('numero').value = '';
		document.getElementById('endereco').value = '';
		document.getElementById('nomepessoa').value = '';
		document.getElementById('fone').value = '';
		document.getElementById('celular').value = '';
		document.getElementById('rg').value = '';
		document.getElementById('ufrg').value = 'Selecione';
		document.getElementById('ufnascimentoaux').value = 'Selecione';
		document.getElementById('cidadenascimentoaux').value = '';
		document.getElementById('dtnascimento').value = '';
		document.getElementById('datavalidadecnh').value = '';
		document.getElementById('ufcnh').value = 'Selecione';
		document.getElementById('categoria').value = '';
		document.getElementById('numregistro').value = '';
		document.getElementById('primeirahabilitacao').value = '';
		document.getElementById('nomepai').value = '';
		document.getElementById('nomemae').value = '';
		document.getElementById('cpfcnpj').value = '';
		document.getElementById('numsegurancacnh').value = '';

		document.getElementById('renach').value = '';
		document.getElementById('cedulacnh').value = '';

		zeracamposref('ref1');
		zeracamposref('ref2');
		zeracamposref('ref3');
		zeracamposref('ref4');
		zeracamposref('ref5');
		zeracamposref('ref6');

	}

	//***************************************************************************************************************************
	//**********   tratamento para referencias **********************************************************************************
	//***************************************************************************************************************************

	function buscaref1() {

		document.getElementById('ref1fone').value = document.getElementById('ref1fone').value.trim();

		if (document.getElementById('ref1fone').value.length >= 10) {

			document.getElementById('ref1fone').value = parseInt(document.getElementById('ref1fone').value);

			vajaxref1 = ajaxInit();

			vajaxref1.open('get', 'formulariomotorista_sql.php?&sq=buscaref&ref=ref1&fone=' + document.getElementById('ref1fone').value);

			vajaxref1.onreadystatechange = function() {
				if (vajaxref1.readyState == 4) {

					resposta = vajaxref1.responseText;

					//alert(resposta);

					arrayresp = window.eval(resposta);
					if (arrayresp != null) {

						for (var p = 0; p < arrayresp.length; p++) {

							document.getElementById(arrayresp[p][0]).value = arrayresp[p][1].trim();

						}

						//if (document.getElementById('ref1sql').value == 'insert' ) {

						//	document.getElementById('ref1nome').value = lecookie('ref1nome');
						//	document.getElementById('ref1contato').value = lecookie('ref1contato');

						//}

						//seleciona a cidade de nascimento do motorista com base nos dados trazidos do banco						
						new dgCidadesEstados({
							cidade: document.getElementById('ref1cidadeaux'),
							estado: document.getElementById('ref1ufaux'),
							estadoVal: document.getElementById('ref1uf').value,
							cidadeVal: document.getElementById('ref1cidade').value
						})
					}
				}
			}

			vajaxref1.send(null);
		}
	}

	function buscaref2() {

		document.getElementById('ref2fone').value = document.getElementById('ref2fone').value.trim();

		if (document.getElementById('ref2fone').value.length >= 10) {

			document.getElementById('ref2fone').value = parseInt(document.getElementById('ref2fone').value);

			vajaxref2 = ajaxInit();

			vajaxref2.open('get', 'formulariomotorista_sql.php?&sq=buscaref&ref=ref2&fone=' + document.getElementById('ref2fone').value);

			vajaxref2.onreadystatechange = function() {
				if (vajaxref2.readyState == 4) {

					resposta = vajaxref2.responseText;

					arrayresp = window.eval(resposta);
					if (arrayresp != null) {

						for (var p = 0; p < arrayresp.length; p++) {

							document.getElementById(arrayresp[p][0]).value = arrayresp[p][1].trim();

						}

						//if (document.getElementById('ref2sql').value == 'insert' ) {

						//	document.getElementById('ref2nome').value = lecookie('ref2nome');
						//	document.getElementById('ref2contato').value = lecookie('ref2contato');

						//}

						//seleciona a cidade de nascimento do motorista com base nos dados trazidos do banco						
						new dgCidadesEstados({
							cidade: document.getElementById('ref2cidadeaux'),
							estado: document.getElementById('ref2ufaux'),
							estadoVal: document.getElementById('ref2uf').value,
							cidadeVal: document.getElementById('ref2cidade').value
						})
					}
				}
			}

			vajaxref2.send(null);
		}
	}

	function buscaref3() {

		document.getElementById('ref3fone').value = document.getElementById('ref3fone').value.trim();

		if (document.getElementById('ref3fone').value.length >= 10) {

			document.getElementById('ref3fone').value = parseInt(document.getElementById('ref3fone').value);

			vajaxref3 = ajaxInit();

			vajaxref3.open('get', 'formulariomotorista_sql.php?&sq=buscaref&ref=ref3&fone=' + document.getElementById('ref3fone').value);

			vajaxref3.onreadystatechange = function() {
				if (vajaxref3.readyState == 4) {

					resposta = vajaxref3.responseText;

					arrayresp = window.eval(resposta);
					if (arrayresp != null) {

						for (var p = 0; p < arrayresp.length; p++) {

							document.getElementById(arrayresp[p][0]).value = arrayresp[p][1].trim();

						}

						//f (document.getElementById('ref3sql').value == 'insert' ) {

						//	document.getElementById('ref3nome').value = lecookie('ref3nome');
						//	document.getElementById('ref3contato').value = lecookie('ref3contato');

						//}

						//seleciona a cidade de nascimento do motorista com base nos dados trazidos do banco						
						new dgCidadesEstados({
							cidade: document.getElementById('ref3cidadeaux'),
							estado: document.getElementById('ref3ufaux'),
							estadoVal: document.getElementById('ref3uf').value,
							cidadeVal: document.getElementById('ref3cidade').value
						})
					}
				}
			}

			vajaxref3.send(null);
		}
	}


	function buscaref4() {

		document.getElementById('ref4fone').value = document.getElementById('ref4fone').value.trim();

		if (document.getElementById('ref4fone').value.length >= 10) {

			document.getElementById('ref4fone').value = parseInt(document.getElementById('ref4fone').value);

			vajaxref4 = ajaxInit();

			vajaxref4.open('get', 'formulariomotorista_sql.php?&sq=buscaref&ref=ref4&fone=' + document.getElementById('ref4fone').value);

			vajaxref4.onreadystatechange = function() {
				if (vajaxref4.readyState == 4) {

					resposta = vajaxref4.responseText;

					arrayresp = window.eval(resposta);
					if (arrayresp != null) {

						for (var p = 0; p < arrayresp.length; p++) {

							document.getElementById(arrayresp[p][0]).value = arrayresp[p][1].trim();

						}

						//if (document.getElementById('ref4sql').value == 'insert' ) {

						//	document.getElementById('ref4nome').value = lecookie('ref4nome');
						//	document.getElementById('ref4contato').value = lecookie('ref4contato');

						//}

						//seleciona a cidade de nascimento do motorista com base nos dados trazidos do banco						
						new dgCidadesEstados({
							cidade: document.getElementById('ref4cidadeaux'),
							estado: document.getElementById('ref4ufaux'),
							estadoVal: document.getElementById('ref4uf').value,
							cidadeVal: document.getElementById('ref4cidade').value
						})
					}
				}
			}

			vajaxref4.send(null);
		}
	}

	function buscaref5() {

		document.getElementById('ref5fone').value = document.getElementById('ref5fone').value.trim();

		if (document.getElementById('ref5fone').value.length >= 10) {

			document.getElementById('ref5fone').value = parseInt(document.getElementById('ref5fone').value);

			vajaxref5 = ajaxInit();

			vajaxref5.open('get', 'formulariomotorista_sql.php?&sq=buscaref&ref=ref5&fone=' + document.getElementById('ref5fone').value);

			vajaxref5.onreadystatechange = function() {
				if (vajaxref5.readyState == 4) {

					resposta = vajaxref5.responseText;

					arrayresp = window.eval(resposta);
					if (arrayresp != null) {

						for (var p = 0; p < arrayresp.length; p++) {

							document.getElementById(arrayresp[p][0]).value = arrayresp[p][1].trim();

						}

						//if (document.getElementById('ref5sql').value == 'insert' ) {

						//	document.getElementById('ref5nome').value = lecookie('ref5nome');
						//	document.getElementById('ref5contato').value = lecookie('ref5contato');

						//}

						//seleciona a cidade de nascimento do motorista com base nos dados trazidos do banco						
						new dgCidadesEstados({
							cidade: document.getElementById('ref5cidadeaux'),
							estado: document.getElementById('ref5ufaux'),
							estadoVal: document.getElementById('ref5uf').value,
							cidadeVal: document.getElementById('ref5cidade').value
						})
					}
				}
			}

			vajaxref5.send(null);
		}
	}


	function buscaref6() {

		document.getElementById('ref6fone').value = document.getElementById('ref6fone').value.trim();

		if (document.getElementById('ref6fone').value.length >= 10) {

			document.getElementById('ref6fone').value = parseInt(document.getElementById('ref6fone').value);

			vajaxref6 = ajaxInit();

			vajaxref6.open('get', 'formulariomotorista_sql.php?&sq=buscaref&ref=ref6&fone=' + document.getElementById('ref6fone').value);

			vajaxref6.onreadystatechange = function() {
				if (vajaxref6.readyState == 4) {

					resposta = vajaxref6.responseText;

					arrayresp = window.eval(resposta);
					if (arrayresp != null) {

						for (var p = 0; p < arrayresp.length; p++) {

							document.getElementById(arrayresp[p][0]).value = arrayresp[p][1].trim();

						}

						//if (document.getElementById('ref6sql').value == 'insert' ) {

						//	document.getElementById('ref6nome').value = lecookie('ref6nome');
						//	document.getElementById('ref6contato').value = lecookie('ref6contato');

						//}

						//seleciona a cidade de nascimento do motorista com base nos dados trazidos do banco						
						new dgCidadesEstados({
							cidade: document.getElementById('ref6cidadeaux'),
							estado: document.getElementById('ref6ufaux'),
							estadoVal: document.getElementById('ref6uf').value,
							cidadeVal: document.getElementById('ref6cidade').value
						})
					}
				}
			}

			vajaxref6.send(null);
		}
	}

	function zeracamposref(ref) {

		//document.getElementById(ref+'sql').value = '';
		document.getElementById(ref + 'nome').value = '';
		document.getElementById(ref + 'fone').value = '';
		document.getElementById(ref + 'cidade').value = '';
		document.getElementById(ref + 'uf').value = '';
		document.getElementById(ref + 'contato').value = '';
		document.getElementById(ref + 'ufaux').value = '';
		document.getElementById(ref + 'cidadeaux').value = '';
		document.getElementById(ref + 'codireferencia').value = '';

	}

	//************************************
	// salva referencias
	//************************************
	function salvareferencias(parcodipessoa, ref) {

		//alert(' codipessoa '+parcodipessoa+' referencia '+ref);

		if (parcodipessoa.length > 0) {

			//define a varivel ajax como a conexao ajax  que foi feita 
			ajax1 = ajaxInit();

			//abre uma instancia ajax ultiliznado get e mandando para a funcao funajax.php
			ajax1.open('get', "formulariomotorista_sql.php?&sq=gravaref&sql=" + document.getElementById(ref + 'sql').value + "&fone=" + document.getElementById(ref + 'fone').value + "&nome=" + document.getElementById(ref + 'nome').value + "&cidade=" + document.getElementById(ref + 'cidadeaux').value + "&uf=" + document.getElementById(ref + 'ufaux').value + "&contato=" + document.getElementById(ref + 'contato').value + "&codipessoa=" + parcodipessoa + "&codireferencia=" + document.getElementById(ref + 'codireferencia').value);

			//a cada vez que o estado mudar chama a funcao preencheoscampos
			ajax1.onreadystatechange = function() {

				if (ajax1.readyState == 4) {

					//define a variavel resposta como a resposta trazida pelo ajax
					resposta = ajax1.responseText;

					//alert(resposta);
					//				document.getElementById('respostaajax').innerHTML =  resposta; 

					//limpa o formulario para nao ficar sujeira se o retorno foi ok				
					//if (resposta.length == 123 ) {

					//					zeracamposref(ref);

					//}				
				}
			}
			//envia a solicitacao ajax
			ajax1.send(null);

		}
	}

	function valida() {

		//alert(document.getElementById('obsficha').value );
		//zera a div avisos
		document.getElementById('avisos').innerHTML = "";

		// validacao de campos digitados pelo usuario
		var vvalidacampo = true;

		//0funcoes/fvalidacpf.js

		//se a nacionalidade for em branco significa que é brasileior dai tem que validar o cpf
		//caso contrário é estrangeioro dai nao precisa validar o cpf	

		if (document.getElementById('nacionalidade').value == '') {

			if (valida_cpf(document.getElementById('cpfcnpj').value, 'CPF/CNPJ')) {
				document.getElementById('cpfcnpj').style.backgroundColor = '#FFFFFF';
			} else {
				document.getElementById('cpfcnpj').style.backgroundColor = "#EE6363";
				document.getElementById('cpfcnpj').focus();
				vvalidacampo = false;
			}
		}


		//tira os espacos em branco
		document.getElementById('endereco').value = document.getElementById('endereco').value.trim().toUpperCase();
		document.getElementById('cep').value = document.getElementById('cep').value.trim().toUpperCase();
		document.getElementById('rg').value = document.getElementById('rg').value.trim().toUpperCase();
		document.getElementById('numregistro').value = document.getElementById('numregistro').value.trim().toUpperCase();
		document.getElementById('nomepai').value = document.getElementById('nomepai').value.trim().toUpperCase();
		document.getElementById('nomemae').value = document.getElementById('nomemae').value.trim().toUpperCase();
		document.getElementById('cpfcnpj').value = document.getElementById('cpfcnpj').value.trim().toUpperCase();
		document.getElementById('nomepessoa').value = document.getElementById('nomepessoa').value.trim().toUpperCase();
		document.getElementById('categoria').value = document.getElementById('categoria').value.trim().toUpperCase();
		document.getElementById('primeirahabilitacao').value = document.getElementById('primeirahabilitacao').value.trim().toUpperCase();
		document.getElementById('renach').value = document.getElementById('renach').value.trim().toUpperCase();
		document.getElementById('obsficha').value = document.getElementById('obsficha').value.trim();


		if (validaformulario_sonumero(document.getElementById('cpfcnpj').value, 11, 11, 'CPF do motorista')) {
			document.getElementById('cpfcnpj').style.backgroundColor = '#FFFFFF';
		} else {
			document.getElementById('cpfcnpj').style.backgroundColor = "#EE6363";
			document.getElementById('cpfcnpj').focus();
			vvalidacampo = false;
		}

		if (validaformulario_letra(document.getElementById('nomepessoa').value, 3, 60, 'Nome Motorista')) {
			document.getElementById('nomepessoa').style.backgroundColor = '#FFFFFF';
		} else {
			document.getElementById('nomepessoa').style.backgroundColor = "#EE6363";
			document.getElementById('nomepessoa').focus();
			vvalidacampo = false;
		}

		if (validaformulario_letra(document.getElementById('ufaux').value, 2, 2, 'Uf Residencia Motorista')) {
			document.getElementById('ufaux').style.backgroundColor = '#FFFFFF';
		} else {
			document.getElementById('ufaux').style.backgroundColor = "#EE6363";
			document.getElementById('ufaux').focus();
			vvalidacampo = false;
		}

		if (validaformulario_sonumero(document.getElementById('cep').value, 0, 8, 'Cep Residenica motorista')) {
			document.getElementById('cep').style.backgroundColor = '#FFFFFF';
		} else {
			document.getElementById('cep').style.backgroundColor = "#EE6363";
			document.getElementById('cep').focus();
			vvalidacampo = false;
		}

		if (validaformulario_alfa(document.getElementById('endereco').value, 3, 90, 'endereco')) {
			document.getElementById('endereco').style.backgroundColor = '#FFFFFF';
		} else {
			document.getElementById('endereco').style.backgroundColor = "#EE6363";
			document.getElementById('endereco').focus();
			vvalidacampo = false;
		}

		if (validaformulario_alfa(document.getElementById('bairro').value, 3, 30, 'bairro')) {
			document.getElementById('bairro').style.backgroundColor = '#FFFFFF';
		} else {
			document.getElementById('bairro').style.backgroundColor = "#EE6363";
			document.getElementById('bairro').focus();
			vvalidacampo = false;
		}

		/*	if (   validaformulario_alfa(document.getElementById('numero').value,1,10,'numero')    ) {
				document.getElementById('numero').style.backgroundColor = '#FFFFFF'; 
		    } else {
				document.getElementById('numero').style.backgroundColor = "#EE6363";
				document.getElementById('numero').focus();
				vvalidacampo = false;		
			}
		*/

		if (validaformulario_letra(document.getElementById('nomepai').value, 5, 70, 'nomepai')) {
			document.getElementById('nomepai').style.backgroundColor = '#FFFFFF';
		} else {
			document.getElementById('nomepai').style.backgroundColor = "#EE6363";
			document.getElementById('nomepai').focus();
			vvalidacampo = false;
		}


		if (validaformulario_alfa(document.getElementById('cidadeaux').value, 2, 50, 'cidade')) {
			document.getElementById('cidadeaux').style.backgroundColor = '#FFFFFF';
		} else {
			document.getElementById('cidadeaux').style.backgroundColor = "#EE6363";
			document.getElementById('cidadeaux').focus();
			vvalidacampo = false;
		}

		if (validaformulario_data(document.getElementById('dtnascimento').value, 10, 10, 'dtnascimento')) {
			document.getElementById('dtnascimento').style.backgroundColor = '#FFFFFF';
		} else {
			document.getElementById('dtnascimento').style.backgroundColor = "#EE6363";
			document.getElementById('dtnascimento').focus();
			vvalidacampo = false;
		}

		/*if (  	validaformulario_alfa(document.getElementById('ufnascimentoaux').value,2,2,'ufnascimento')  ) {
		document.getElementById('ufnascimentoaux').style.backgroundColor = '#FFFFFF'; 
    } else {
		document.getElementById('ufnascimentoaux').style.backgroundColor = "#EE6363";
		document.getElementById('ufnascimentoaux').focus();
		vvalidacampo = false;		
	}			
	
	if ( validaformulario_alfa(document.getElementById('cidadenascimentoaux').value,2,50,'cidadenascimento') ) {
		document.getElementById('cidadenascimentoaux').style.backgroundColor = '#FFFFFF'; 
    } else {
		document.getElementById('cidadenascimentoaux').style.backgroundColor = "#EE6363";
		document.getElementById('cidadenascimentoaux').focus();
		vvalidacampo = false;		
	}		
	*/
		if (validaformulario_sonumero(document.getElementById('fone').value, 10, 11, 'fone')) {
			document.getElementById('fone').style.backgroundColor = '#FFFFFF';
		} else {
			document.getElementById('fone').style.backgroundColor = "#EE6363";
			document.getElementById('fone').focus();
			vvalidacampo = false;
		}

		if (validaformulario_sonumero(document.getElementById('celular').value, 0, 11, 'celular')) {
			document.getElementById('celular').style.backgroundColor = '#FFFFFF';
		} else {
			document.getElementById('celular').style.backgroundColor = "#EE6363";
			document.getElementById('celular').focus();
			vvalidacampo = false;
		}

		if (validaformulario_letra(document.getElementById('ufrg').value, 2, 2, 'ufrg')) {
			document.getElementById('ufrg').style.backgroundColor = '#FFFFFF';
		} else {
			document.getElementById('ufrg').style.backgroundColor = "#EE6363";
			document.getElementById('ufrg').focus();
			vvalidacampo = false;
		}

		if (validaformulario_letra(document.getElementById('nomemae').value, 5, 70, 'nomemae')) {
			document.getElementById('nomemae').style.backgroundColor = '#FFFFFF';
		} else {
			document.getElementById('nomemae').style.backgroundColor = "#EE6363";
			document.getElementById('nomemae').focus();
			vvalidacampo = false;
		}


		if (validaformulario_alfa(document.getElementById('rg').value, 4, 15, 'rg')) {
			document.getElementById('rg').style.backgroundColor = '#FFFFFF';
		} else {
			document.getElementById('rg').style.backgroundColor = "#EE6363";
			document.getElementById('rg').focus();
			vvalidacampo = false;
		}

		/*	
	// verifico se precisa gravar cnh
	if ( document.getElementById('numregistro').value.length > 1 ) {
				
		if (  	validaformulario_sonumero(document.getElementById('numregistro').value,2,15,'numregistro')   ) {
			document.getElementById('numregistro').style.backgroundColor = '#FFFFFF'; 
		} else {
			document.getElementById('numregistro').style.backgroundColor = "#EE6363";
			document.getElementById('numregistro').focus();
			vvalidacampo = false;		
		}	
			
		if (  validaformulario_data(document.getElementById('datavalidadecnh').value,10,10,'datavalidadecnh')   ) {
			document.getElementById('datavalidadecnh').style.backgroundColor = '#FFFFFF'; 
		} else {
			document.getElementById('datavalidadecnh').style.backgroundColor = "#EE6363";
			document.getElementById('datavalidadecnh').focus();
			vvalidacampo = false;		
		}

		if (  validaformulario_alfa(document.getElementById('categoria').value,1,6,'categoria')  ) {
			document.getElementById('categoria').style.backgroundColor = '#FFFFFF'; 
		} else {
			document.getElementById('categoria').style.backgroundColor = "#EE6363";
			document.getElementById('categoria').focus();
			vvalidacampo = false;		
		}

		if (  validaformulario_alfa(document.getElementById('ufcnh').value,1,3,'UF cnh')  ) {
			document.getElementById('ufcnh').style.backgroundColor = '#FFFFFF'; 
		} else {
			document.getElementById('ufcnh').style.backgroundColor = "#EE6363";
			document.getElementById('ufcnh').focus();
			vvalidacampo = false;		
		}
		
		// verifica se primeira habilitacao é de minas gerais		
		if ( document.getElementById('ufcnh').value == 'MG' ) {
		
			if ( document.getElementById('primeirahabilitacao').value.length < 10 ) {
			
				alert('Sr. Usuario, para CNH ( carteira de habilitação ) do estado de (MG) faz-se necessário inserir a data da primeira habilitação, para que seja possível a consulta  !');
				document.getElementById('primeirahabilitacao').style.backgroundColor = "#EE6363";
				document.getElementById('primeirahabilitacao').focus();
				vvalidacampo = false;	
					
			}
						
		}	
		
		if ( document.getElementById('primeirahabilitacao').value.length > 0 ) {
								
			if (  validaformulario_data(document.getElementById('primeirahabilitacao').value,10,10,'primeirahabilitacao')  ) {
				document.getElementById('primeirahabilitacao').style.backgroundColor = '#FFFFFF'; 
			} else {
				document.getElementById('primeirahabilitacao').style.backgroundColor = "#EE6363";
				document.getElementById('primeirahabilitacao').focus();
				vvalidacampo = false;		
			}
		}	
				
		// verifica se primeira habilitacao é de minas gerais		
		if ( document.getElementById('ufcnh').value == 'TO' || document.getElementById('ufcnh').value == 'MT' ) {
		
			if ( document.getElementById('renach').value.length < 3 ) {
			
				alert('Sr. Usuario, para Carteira de Habilitação do Detran-TO,MT, faz-se necessário inserir o RENACH, para que seja possível a consulta  !');
				document.getElementById('renach').style.backgroundColor = "#EE6363";
				document.getElementById('renach').focus();
				vvalidacampo = false;	
					
			}else{
								
				if (  validaformulario_sonumero(document.getElementById('renach').value,3,15,'renach')  ) {
					document.getElementById('renach').style.backgroundColor = '#FFFFFF'; 
				} else {
					document.getElementById('renach').style.backgroundColor = "#EE6363";
					document.getElementById('renach').focus();
					vvalidacampo = false;		
				}
			}								
		}	
		if ( document.getElementById('ufcnh').value == 'ES'  ) {
		
			if ( document.getElementById('cedulacnh').value.length < 3 ) {
			
				alert('Sr. Usuario, para Carteira de Habilitação do Detran-ES, faz-se necessário inserir a CEDULA DA CNH, para que seja possível a consulta  !');
				document.getElementById('cedulacnh').style.backgroundColor = "#EE6363";
				document.getElementById('cedulacnh').focus();
				vvalidacampo = false;	
					
			}else{
								
				if (  validaformulario_sonumero(document.getElementById('cedulacnh').value,3,15,'cedulacnh')  ) {
					document.getElementById('cedulacnh').style.backgroundColor = '#FFFFFF'; 
				} else {
					document.getElementById('cedulacnh').style.backgroundColor = "#EE6363";
					document.getElementById('cedulacnh').focus();
					vvalidacampo = false;		
				}
			}								
		}	
		
		if ( document.getElementById('ufcnh').value == 'SE'  ) {
		
			if ( document.getElementById('numsegurancacnh').value.length < 3 ) {
			
				alert('Sr. Usuario, para Carteira de Habilitação do Detran-SE, faz-se necessário inserir a numsegurancacnh DA CNH, para que seja possível a consulta  !');
				document.getElementById('numsegurancacnh').style.backgroundColor = "#EE6363";
				document.getElementById('numsegurancacnh').focus();
				vvalidacampo = false;	
					
			}else{
								
				if (  validaformulario_sonumero(document.getElementById('numsegurancacnh').value,3,15,'numsegurancacnh')  ) {
					document.getElementById('numsegurancacnh').style.backgroundColor = '#FFFFFF'; 
				} else {
					document.getElementById('numsegurancacnh').style.backgroundColor = "#EE6363";
					document.getElementById('numsegurancacnh').focus();
					vvalidacampo = false;		
				}
			}								
		}	

		if ( document.getElementById('ufcnh').value == 'CE'  ) {
		
			if ( document.getElementById('numsegurancacnh').value.length < 3 ) {
			
				alert('Sr. Usuario, para Carteira de Habilitação do Detran-SE, faz-se necessário inserir a numsegurancacnh DA CNH, para que seja possível a consulta  !');
				document.getElementById('numsegurancacnh').style.backgroundColor = "#EE6363";
				document.getElementById('numsegurancacnh').focus();
				vvalidacampo = false;	
					
			}else{
								
				if (  validaformulario_sonumero(document.getElementById('numsegurancacnh').value,3,15,'numsegurancacnh')  ) {
					document.getElementById('numsegurancacnh').style.backgroundColor = '#FFFFFF'; 
				} else {
					document.getElementById('numsegurancacnh').style.backgroundColor = "#EE6363";
					document.getElementById('numsegurancacnh').focus();
					vvalidacampo = false;		
				}
			}								
		}	
		
	}	
    
	*/

		//	alert('1'+document.getElementById('obsficha').value );
		return vvalidacampo;


	}
	//alert('teste final');
</script>