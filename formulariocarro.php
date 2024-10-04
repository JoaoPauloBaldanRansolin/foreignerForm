	<?php session_start();

	require_once('../0funcoes/faviso2.php');

	//se o usuario nao fez login ou o login expirou para a executacao da pagina e emite aviso para o usuario efeturar um novo login
	If ( $_SESSION['usuario'] == '' && $_SESSION['senha']  == '') {
		die(faviso2("Seu login expirou","por favor efetue novo  <a href='../0usuario/index.php'>Login</a> !")); 
	} 

	If ( $_SESSION['nivelativo']  == 0 ) {

	    die(faviso2("Seu login nao tem privilegio para acessar esta pagina","por favor contacte o administrador de sistema")); 
		//0 - inativo
		//1 - basico ( cliente )
		//2 - medio ( seguradora )
		//3 - avancado (century)
		//4 - avancado (century avancado )
		//5 - mega usuario (todos os acesos)
	}


	?>

	<!-- carrega a funcao que inicia o ajax -->
	<script language='javascript' src='../0funcoes/ajaxInit.js'></script>

	<!-- carrega a funcao que valida formularios -->
	<script language='javascript' src='../0funcoes/validaformulario.js?v2'></script>

	<!-- valida cpf -->
	<script language='javascript' src='../0funcoes/fvalidacpf.js'></script>

	<!-- valida cnpj -->
	<script language='javascript' src='../0funcoes/fvalidacnpj.js'></script>

	<!-- inclui funcoes para exibir as cidades -->
	<script language="JavaScript" src="../0funcoes/cidade.js"></script>

	<!-- inclui funcoes para exibir cokie -->
	<script language="JavaScript" src="../0funcoes/cookie.js"></script>


	<!-- abre a tela de iniciorapido -->
	<link rel='stylesheet' href='../0funcoes/estilo.css'>

	<!-- telapara fazer upload de arquivos de mot e veiculo -->
	<script language='javascript' src='../0funcoes/telauploadmotoristaveiculo.js'></script>



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



	<!-- monta o cabecalho -->
	<?php require_once('../0layout/elastix/cabecelastix.php');
	?>
	<!-- monta o menu -->
	<?php
		// if temporario ate que eu arrume a amarracao de menu junto das telas
		if ($_GET['menuoff']	!= 'true') 
			require_once('../0funcoes/menucentury.php'); 
	?>
	<!-- cria o formulario -->

	<div class="geral">

		<!-- esta div mostra a tela abertura rapida //-->
		<div id="divpop"></div>

		<body onload="document.getElementById('placa').focus();">

			<div id='avisos' class='alert alert-danger' role='alert'> <img src="../0bmp/interrogacao1.png" align="absmiddle" border="0" width='30' height='30' />
				Sr Cliente, afim de agilizar pesquisas,
				<b>obrigatorio anexar documento veiculo/reboque, com QRCODE legível!</b>
			</div>

			<table border="0" align=center width="100%" class='redonda'>
				<tr class="moduleTitle">
					<td class="moduleTitle" valign="middle" colspan=4> <img src='../0bmp/carro2.png' width='25' height='25' border='0' align='absmiddle'> <img src="../0layout/elastix/1x1.gif" align="absmiddle" border="0">Cadastro de carro / reboque / semireboque </td>
					<td class="moduleTitle" valign="middle">
						<div id='msgajax'></div>
					</td>
				</tr>
				<!-- cria os campos hidden que serao ultilizados na gravacao dos dados do motorista -->
				<input type="hidden" name="sqlplaca" id="sqlplaca" />
				<input type='hidden' name='contaprincipal' id='contaprincipal' value=<?php echo $_SESSION['contaprincipal']
																						?>>
				<input type='hidden' name='cpfcnpjconta' id='cpfcnpjconta' value=<?php echo $_SESSION['cpfcnpjconta']
																					?>>
				<input type='hidden' id='importacookie' value=''>

				<tr>
					<td>
						<!-- Campo novo, função 'apagacampos()' vai ocultar certos campos quando o cliente selecionar como "placa estrangeira".-->
						<select class='redonda' id="nacionalidade" onchange="apagacampos(this.value)">
							<option value=''>CPF BRA
							<option value='1'>RUC PAR
							<option value='2'>CUIT ARG
							<option value='3'>RUT CHI
							<option value='4'>RUT URU

						</select>
					</td>
					<td> Placa: </td>
					<td><input type='text' id='placa' name='placa' size='8' maxlength='8' tabindex=1 value=" <? //php echo $_GET[placa];?>" onchange="buscaplaca(this.value);">
						<button type='submit' class='btn btn-success upload btn-sm' onclick='buscaplaca(this.value);'> <i class='fa fa-search fa-sm'></i> </button>
						<select id='ufplaca' name='ufplaca' tabindex=2 onchange="criacookie('ufplacacarro',this.value);"></select>
					</td>
					<td> Categoria: </td>
					<td><select class='redonda' id='categoria' name='categoria' tabindex=3 onchange="criacookie('categoriacarro',this.value);">
							<option value=''>Selecione
							<option value='ARTICULADO'>ARTICULADO (cavalo/trator/Onibus) = CAT E
							<option value='TRUCK'>TRUCK (Toco/3-2eixos) Carga Acima 3.5 TON = CAT C
							<option value='AUTO'>AUTO (Utilitario) Ate 3.5 TON = Categoria B
							<option value='MOTO'>MOTO (Triciclo/3-2rodas) = Categoria A
							<option value='IMPLEMENTO'>IMPLEMENTOS (Carreta/Reboque/Semi-reboque)
						</select></td>
					<td></td>
					<td></td>
				</tr>

				<tr>
					<td> Marca: </td>
					<td><input type='text' id='marca' name='marca' size='26' maxlength='18 ' tabindex=4 onchange="criacookie('marcacacarro',this.value);"></td>
					<td> Modelo: </td>
					<td><input type='text' id='modelo' name='modelo' size='40' maxlength='40' tabindex=5 onchange="criacookie('modelocarro',this.value);"></td>
				</tr>
				<tr id="tr_chassi-renavam">
					<td> Chassi: </td>
					<td><input type='text' id='chassi' name='chassi' size='30' maxlength='25' tabindex=6 onchange="criacookie('chassicacarro',this.value);"></td>
					<td> Renavam: </td>
					<td><input type='text' id='renavan' name='renavan' size='15' maxlength='11' tabindex=7 onchange="criacookie('renavancarro',this.value);"> RNTRC: <input type='text' id='antt' name='antt' size='17' maxlength='15' tabindex=8></td>
				</tr>

				<tr>
					<td> Ano Fabricacao: </td>
					<td><input type='text' id='anofabricacao' name='anofabricacao' size='6' maxlength='4' tabindex=9 onchange="criacookie('anofabricacaocacarro',this.value);"> </td>
					<td> Cor: </td>
					<td> <select class='redonda' id='cor' name='cor' tabindex=10 onchange="criacookie('corcarro',this.value);">
							<option value='Selecione'>Selecione
							<option>AMARELO
							<option>AZUL
							<option>BEGE
							<option>BRANCO
							<option>CINZA
							<option>DOURADO
							<option>FANTASIA
							<option>PRATA
							<option>LARANJA
							<option>MARROM
							<option>PRETO
							<option>ROXO
							<option>VERDE
							<option>VERMELHO
							<option>ROSA
							<option>INDEFINIDO
						</select></td>
				</tr>

				<input type='hidden' id='codipessoa' name='codipessoa' />
				<input type="hidden" name="sql" id="sql" />
				<input type='hidden' name='cidade' id='cidade' />
				<input type='hidden' name='uf' id='uf' />
				<input type='hidden' name='tipopessoa' id='tipopessoa' />

				<tr>
					<td> Cpf/Cnpj: </td>
					<td><input type='text' id='cpfcnpj' maxlength="20" size="20" onchange="buscaajax(this.value);" tabindex=11>

						<button type='submit' class='btn btn-success upload btn-sm' onclick='buscaajax(this.value);'> <i class='fa fa-search fa-sm'></i> </button>


					</td>
					<td> Nome Completo: </td>
					<td><input type='text' id='nomepessoa' size='40' maxlength='60' tabindex=12 onchange="criacookie('nomepropcarro',this.value);"></td>
				</tr>
				<input type='hidden' id='cep' name='cep'>
				<input type='hidden' id='endereco' name='endereco'>
				<tr id="tr_uf">
					<td> UF: </td>
					<td> <select class='redonda' id="ufaux" tabindex=15 onchange="criacookie('ufauxcarro',this.value);"></select></td>
					<td> Cidade: </td>
					<td> <select class='redonda' id="cidadeaux" tabindex=16 onchange="criacookie('cidadeauxcarro',this.value);">
							<option value=''>Selecione a UF
						</select></td>
				</tr>
				<tr id="tr_fone">
					<td> Fone fixo: </td>
					<td> <input type='text' id='fone' name='fone' size='15' maxlength='15' tabindex=17 onchange="criacookie('fonecarro',this.value);"></td>
					<td> Celular: </td>
					<td> <input type='text' id='celular' name='celular' size='15' maxlength='15' tabindex=18 onchange="criacookie('celularcarro',this.value);">
						N.o Seguranca: <input type='text' id='numeroseguranca' size='12' maxlength='15' tabindex=19 "></td>
				</tr> 	                
					
				<?php

				//transcourier
				if ($_SESSION['conta'] == '2205200850') {
					//if ( true ) {

					echo  "			
							<tr><td> Tara: </td><td><input type='text' id='tara'  size='20' maxlength='20' ></td>
								<td> Cap. de carga em Kg: </td><td> <input type='text' id='capacidadecargakg' size='20' maxlength='20' ></td></tr>
							<tr><td> Cap. de carga em M3: </td><td> <input type='text' id='capacidadecargam3' size='20' maxlength='20' ></td>
								<td> Tipo de Carroceria: </td><td> <input type='text' id='tipocarroceria' size='20' maxlength='20' ></td></tr>
							<tr><td> Transportador: </td><td> <input type='text' id='cpfcnpjtransportador' size='20' maxlength='14' ></td>
								<td> Nome Transportador: </td><td> <input type='text' id='nometransportador' size='20' maxlength='20' ></td></tr>				
						";
				} else {

					echo  "	
							<input type='hidden' id='tara' >
							<input type='hidden' id='capacidadecargakg' >
							<input type='hidden' id='capacidadecargam3' >
							<input type='hidden' id='tipocarroceria' >
							<input type='hidden' id='cpfcnpjtransportador' >
							<input type='hidden' id='nometransportador' >		
						";
				}


				?>	
					
				<!-- elininamask() ta declarado aqui mesmo-->
				<table border=" 0" align=center width=100%>
				<tr class="moduleTitle">
					<td class="moduleTitle" valign="middle" colspan='4'>&nbsp;&nbsp;<img src="../0layout/elastix/1x1.gif" align="absmiddle" border="0" />
						Observacoes do veiculo:
					</td>
				</tr>
				<tr>
					<td><input type='text' id='obsficha' size='100' maxlength='130' tabindex=59></td>
				</tr>
			</table>
			</table>
			<div id='botao'></div>
		</body>
	</div>
	<script>
		function apagacampos(valor) {
			// Lista de id que serão escondidos
			const mostrar = [
				"ufplaca",
				"tr_chassi-renavam",
				"tr_uf",
				"tr_fone",
			];

			if (valor === "") {
				// Quando placa brasileira:
				mostrar.forEach(id => {
					document.getElementById(id).hidden = false;
				});
			} else {
				// Quando placa estrangeira:
				mostrar.forEach(id => {
					document.getElementById(id).hidden = true;
				});
			}
		}
	</script>
	<script language='JavaScript' type='text/javascript' charset='utf-8'>
		//for (var p = 0; p < arrayresp.length; p++ ) {


		function incrementazeroantes(str, zeros) {


			for (var i = str.length; i < zeros; i++) {

				str = '0' + str;

			}

			return str;

		}


		function upload() {

			// funcao uploaddocumentos() está na pasta /funcoes/telauploadmotoristaveiculo.js 
			//e incluido em /0interrisco/registrospendentes.php



			if (document.getElementById('categoria').value == 'IMPLEMENTO') {

				uploaddocumentos('', '', '', document.getElementById('placa').value);
			} else {

				uploaddocumentos('', '', document.getElementById('placa').value, '');
			}



		}

		//0funcoes/cidade.js
		new dgCidadesEstados({
			cidade: document.getElementById('cidadeaux'),
			estado: document.getElementById('ufaux')
		})

		fpopulauf('ufplaca', 'Selecione');


		//lecookie esta na funcao cookie.js
		var temcookie = lecookie('placacarro');


		if (temcookie.length == 7) {

			decisao = confirm("Sr. Cliente deseja recuperar os dados digitados da ultima sessao ?");

			if (decisao) {

				document.getElementById('placa').value = temcookie;
				//buscaajax(document.getElementById('cpfcnpj').value);

				//alert(temcookie);

				document.getElementById('importacookie').value = 'sim';

				buscaplaca(document.getElementById('placa').value);

			} else {

				//funcao declarada aqui mesmo
				eliminacookie();

				if (document.getElementById('placa').value != '') {

					buscaplaca(document.getElementById('placa').value);

				}
			}
		}

		//esta funcaoesta em funcoes/cookie.js
		function eliminacookie() {

			//apagacookie esta na funcao validaformulario.js
			apagacookie('placacarro');
			apagacookie('categoriacarro');
			apagacookie('ufplacacarro');
			apagacookie('marcacacarro');
			apagacookie('modelocarro');
			apagacookie('chassicacarro');
			apagacookie('renavancarro');
			apagacookie('anofabricacaocacarro');
			apagacookie('corcarro');
			apagacookie('cpfcnpjcarro');
			apagacookie('nomepropcarro');
			apagacookie('ufauxcarro');
			apagacookie('cidadeauxcarro');
			apagacookie('fonecarro');
			apagacookie('celularcarro');

		}

		//inicia a funcao que busca os dados do motorista no banco de dados ultilizando ajax
		function buscaplaca(parchave) {

			//var $placa = document.getElementById('placa').value;
			document.getElementById('placa').value = document.getElementById('placa').value.replace('-', '');
			document.getElementById('placa').value = document.getElementById('placa').value.replace(' ', '');
			document.getElementById('placa').value = document.getElementById('placa').value.trim().toUpperCase();

			//document.getElementById('placa').value = $placa;

			document.getElementById('avisos').innerHTML = '';

			// verifica se o usuario digitou a conta
			if (validaformulario_placa(document.getElementById('placa').value, 7, 7, 'placa')) {


				//esta funcaoesta em funcoes/cookie.js
				criacookie('placacarro', document.getElementById('placa').value);

				//define a varivel ajax como a conexao ajax  que foi feita 
				vajax = ajaxInit();

				//abre uma instancia ajax ultiliznado get e mandando para a funcao funajax.php
				vajax.open('get', 'funajax.php?&sq=buscaplaca&placa=' + document.getElementById('placa').value);

				//a cada vez que o estado mudar chama a funcao preencheoscampos
				vajax.onreadystatechange = function() {

					// Exibe a mensagem "Aguarde..." enquanto carrega
					if (vajax.readyState == 1) {
						document.getElementById('msgajax').innerHTML = 'abrindo formulario';
					}

					if (vajax.readyState == 4 && vajax.status == 200) {

						document.getElementById('msgajax').innerHTML = 'Pronto!';


						//define a variavel resposta como a resposta trazida pelo ajax
						resposta = vajax.responseText;

						//document.getElementById('avisos').innerHTML =  resposta;//resposta;   

						//transforma a resposta trazida do banco em um array
						arrayresp = window.eval(resposta);

						//se trouxe algum valor do banco de dados
						if (arrayresp != null) {

							// mostra resposta padrao do ajax

							for (var p = 0; p < arrayresp.length; p++) {

								document.getElementById(arrayresp[p][0]).value = arrayresp[p][1].trim();

							}

							//alert('sql = '+document.getElementById('sql').value);
							if ((document.getElementById('importacookie').value == 'sim') && (document.getElementById('sql').value == 'insert')) {

								//lecookie esta na funcao validaformulario.js

								buscacep();
								document.getElementById('categoria').value = lecookie('categoriacarro');
								document.getElementById('ufplaca').value = lecookie('ufplacacarro');
								document.getElementById('marca').value = lecookie('marcacacarro');
								document.getElementById('modelo').value = lecookie('modelocarro');
								document.getElementById('chassi').value = lecookie('chassicacarro');
								document.getElementById('renavan').value = lecookie('renavancarro');
								document.getElementById('anofabricacao').value = lecookie('anofabricacaocacarro');
								document.getElementById('cor').value = lecookie('corcarro');
								document.getElementById('cpfcnpj').value = lecookie('cpfcnpjcarro');

								if (document.getElementById('cpfcnpj').value.length == 11) {

									buscaajax(document.getElementById('cpfcnpj').value);

								}

								document.getElementById('nomepessoa').value = lecookie('nomepropcarro');
								document.getElementById('endereco').value = lecookie('enderecocarro');
								document.getElementById('ufaux').value = lecookie('ufauxcarro');
								document.getElementById('cidadeaux').value = lecookie('cidadeauxcarro');
								document.getElementById('fone').value = lecookie('fonecarro');
								document.getElementById('celular').value = lecookie('celularcarro');


							}

							new dgCidadesEstados({
								cidade: document.getElementById('cidadeaux'),
								estado: document.getElementById('ufaux'),
								estadoVal: document.getElementById('uf').value,
								cidadeVal: document.getElementById('cidade').value
							})


						}

						document.getElementById('msgajax').innerHTML = '';

						tlb = "<hr>";
						tlb += "<div class='row'>";
						tlb += "	<div class='col-md-1 '></div>";
						tlb += "	<div class='col-md-10 '>";

						if (document.getElementById('placa').value != '') {

							//if (document.getElementById('categoria').value != 'IMPLEMENTO')
							tlb += "	<button type='submit' class='btn btn-success upload btn-lg' onclick=\"window.open('../0irupload/uploadtela.php?&chavemotplaca=Placa Veiculo&chave=" + document.getElementById('placa').value + "','', 'width=500,height=400,location=no');\"> <i class='fa fa-cloud-upload-alt fa-lg' ></i> Upload Veiculo </button>  ";
							//else {
							tlb += "	<button type='submit' class='btn btn-success upload btn-lg' onclick=\"window.open('../0irupload/uploadtela.php?&chavemotplaca=Placa Reboque&chave=" + document.getElementById('placa').value + "','', 'width=500,height=400,location=no');\"> <i class='fa fa-cloud-upload-alt fa-lg' ></i> Upload Reboque </button>  ";
							tlb += "	<button type='submit' class='btn btn-success upload btn-lg' onclick=\"window.open('../0irupload/uploadtela.php?&chavemotplaca=Placa SemiReboque&chave=" + document.getElementById('placa').value + "','', 'width=500,height=400,location=no');\"> <i class='fa fa-cloud-upload-alt fa-lg' ></i> Upload SemiReboque </button>  ";
							tlb += "	<button type='submit' class='btn btn-success upload btn-lg' onclick=\"window.open('../0irupload/uploadtela.php?&chavemotplaca=Placa TerceiroReboque&chave=" + document.getElementById('placa').value + "','', 'width=500,height=400,location=no');\"> <i class='fa fa-cloud-upload-alt fa-lg' ></i> Upload TerceiroReboque </button>  ";
							//}
						}

						tlb += "	<button type='submit' class='btn btn-success upload btn-lg' onclick=\"salva()\"> Salvar dados</button>  ";
						tlb += "</div>";
						tlb += "	<div class='col-md-1 '></div>";
						tlb += "</div>";

						document.getElementById('botao').innerHTML = tlb;



					}
				}

				//envia a solicitacao ajax
				vajax.send(null);

			}
		}


		function buscacep() {

			//alert('ok');

			document.getElementById('avisos').innerHTML = "";

			document.getElementById('cep').value = document.getElementById('cep').value.replace(/[^0-9]/g, '');


			//document.getElementById('cep').value = document.getElementById('cep').value.replace('-','');


			// verifica se o usuario digitou a conta
			if (document.getElementById('cep').value.length == 8) {

				//define a varivel ajax como a conexao ajax  que foi feita 
				vajax = ajaxInit();

				//abre uma instancia ajax ultiliznado get e mandando para a funcao funajax.php
				vajax.open('get', 'funajax.php?&sq=buscacep&cep=' + document.getElementById('cep').value);

				//a cada vez que o estado mudar chama a funcao preencheoscampos
				vajax.onreadystatechange = function() {

					// Exibe a mensagem "Aguarde..." enquanto carrega
					if (vajax.readyState == 1) {
						document.getElementById('msgajax').innerHTML = 'Buscando cep...';
					}

					if (vajax.readyState == 4 && vajax.status == 200) {

						document.getElementById('msgajax').innerHTML = 'Pronto!';

						//define a variavel resposta como a resposta trazida pelo ajax
						resposta = vajax.responseText;

						//document.getElementById('avisos').innerHTML = resposta;   

						//transforma a resposta trazida do banco em um array
						arrayresp = window.eval(resposta);

						//se trouxe algum valor do banco de dados
						if (arrayresp != null) {

							for (var p = 0; p < arrayresp.length; p++) {

								//                      nome do campo            valor do campo
								document.getElementById(arrayresp[p][0]).value = arrayresp[p][1];


							}


							new dgCidadesEstados({
								cidade: document.getElementById('cidadeaux'),
								estado: document.getElementById('ufaux'),
								estadoVal: document.getElementById('uf').value,
								cidadeVal: document.getElementById('cidade').value
							})


						}

						document.getElementById('msgajax').innerHTML = '';

					}
				}

				//envia a solicitacao ajax
				vajax.send(null);
				document.getElementById('endereco').value.setfocus();

			}
		}


		//inicia a funcao que busca os dados do motorista no banco de dados ultilizando ajax
		function buscaajax(parchave) {


			document.getElementById('cpfcnpj').value = document.getElementById('cpfcnpj').value.replace(/[^0-9]/g, '');


			document.getElementById('cpfcnpj').value = document.getElementById('cpfcnpj').value.trim().toUpperCase();



			//verifica se o cpf / cnpj é valido
			if (document.getElementById('cpfcnpj').value.length == 14) {

				document.getElementById('tipopessoa').value = 'CNPJ';

				//0funcoes/fvalidacpf.js
				if (!valida_cnpj(document.getElementById('cpfcnpj').value, 'CPF/CNPJ'))
					return false;

			} else if (document.getElementById('cpfcnpj').value.length == 11) {

				document.getElementById('tipopessoa').value = 'CPF';

				//0funcoes/fvalidacnpj.js
				if (!valida_cpf(document.getElementById('cpfcnpj').value, 'CPF/CNPJ'))
					return false;

			} else {

				alert('Sr usuario, cpf/cnpj proprietario carro nao esta correto !');
				return false;
			}

			criacookie('cpfcnpjcarro', this.value);


			//define a varivel ajax como a conexao ajax  que foi feita 
			vajax = ajaxInit();

			//abre uma instancia ajax ultiliznado get e mandando para a funcao funajax.php
			vajax.open('get', 'funajax.php?&sq=buscaproprietario&cpfcnpj=' + document.getElementById('cpfcnpj').value);

			//a cada vez que o estado mudar chama a funcao preencheoscampos
			vajax.onreadystatechange = function() {

				//******************
				// Exibe a mensagem "Aguarde..." enquanto carrega
				if (vajax.readyState == 1) {
					document.getElementById('msgajax').innerHTML = 'Buscando formulario...';
				}

				if (vajax.readyState == 4 && vajax.status == 200) {

					//define a variavel resposta como a resposta trazida pelo ajax
					resposta = vajax.responseText;

					//alert(resposta);

					document.getElementById('msgajax').innerHTML = 'Pronto!';

					//transforma a resposta trazida do banco em um array
					arrayresp = window.eval(resposta);

					//se trouxe algum valor do banco de dados
					if (arrayresp != null) {

						// mostra resposta padrao do ajax

						for (var p = 0; p < arrayresp.length; p++) {

							document.getElementById(arrayresp[p][0]).value = arrayresp[p][1].trim();

						}

						new dgCidadesEstados({
							cidade: document.getElementById('cidadeaux'),
							estado: document.getElementById('ufaux'),
							estadoVal: document.getElementById('uf').value,
							cidadeVal: document.getElementById('cidade').value
						})
					}

					document.getElementById('msgajax').innerHTML = '';


				}

				//******************
			}

			//envia a solicitacao ajax
			vajax.send(null);

		}

		function valida() {

			document.getElementById('cpfcnpjtransportador').value = document.getElementById('cpfcnpjtransportador').value.replace(/[^0-9]/g, '');
			document.getElementById('fone').value = document.getElementById('fone').value.replace(/[^0-9]/g, '');
			document.getElementById('celular').value = document.getElementById('celular').value.replace(/[^0-9]/g, '');
			document.getElementById('antt').value = incrementazeroantes(document.getElementById('antt').value, '9');
			document.getElementById('placa').value = document.getElementById('placa').value.trim().toUpperCase();
			document.getElementById('chassi').value = document.getElementById('chassi').value.trim().toUpperCase();
			document.getElementById('renavan').value = document.getElementById('renavan').value.trim().toUpperCase();
			document.getElementById('marca').value = document.getElementById('marca').value.trim().toUpperCase();
			document.getElementById('modelo').value = document.getElementById('modelo').value.trim().toUpperCase();
			document.getElementById('nomepessoa').value = document.getElementById('nomepessoa').value.trim().toUpperCase();
			document.getElementById('cpfcnpj').value = document.getElementById('cpfcnpj').value.trim().toUpperCase();
			document.getElementById('anofabricacao').value = document.getElementById('anofabricacao').value.trim().toUpperCase();

			//verifica se o cpf / cnpj é valido
			if (document.getElementById('cpfcnpj').value.length == 14) {

				document.getElementById('tipopessoa').value = 'CNPJ';

				//0funcoes/fvalidacpf.js
				if (!valida_cnpj(document.getElementById('cpfcnpj').value, 'CPF/CNPJ'))
					return false;

			} else if (document.getElementById('cpfcnpj').value.length == 11) {

				document.getElementById('tipopessoa').value = 'CPF';

				//0funcoes/fvalidacnpj.js
				if (!valida_cpf(document.getElementById('cpfcnpj').value, 'CPF/CNPJ'))
					return false;

			} else {

				alert('Sr usuario, cpf/cnpj proprietario carro nao esta correto !');
				return false;
			}

			//../0funcoes/validaformulario.js 
			if (validaformulario_alfa(document.getElementById('sqlplaca').value, 1, 10, 'SQL Placa') &&
				validaformulario_placa(document.getElementById('placa').value, 7, 7, 'Placa') &&
				validaformulario_alfa(document.getElementById('ufplaca').value, 2, 2, 'UF Placa') &&
				validaformulario_alfa(document.getElementById('chassi').value, 0, 25, 'Chassi') &&
				validaformulario_sonumero(document.getElementById('renavan').value, 9, 11, 'Renavan') &&
				validaformulario_sonumero(document.getElementById('antt').value, 0, 16, 'Antt') &&
				validaformulario_alfa(document.getElementById('marca').value, 2, 30, 'Marca') &&
				validaformulario_alfa(document.getElementById('modelo').value, 3, 60, 'Modelo') &&
				validaformulario_alfa(document.getElementById('categoria').value, 2, 60, 'Categoria') &&
				validaformulario_sonumero(document.getElementById('anofabricacao').value, 0, 5, 'Ano Fabricacao') &&
				validaformulario_alfa(document.getElementById('cor').value, 1, 10, 'Cor') &&
				validaformulario_letra(document.getElementById('nomepessoa').value, 1, 60, 'Nome Pessoa') &&
				validaformulario_alfa(document.getElementById('tipopessoa').value, 1, 4, 'Tipo pessoa CPF-CNPJ') &&
				validaformulario_alfa(document.getElementById('ufaux').value, 0, 2, 'UF - residencia') &&
				validaformulario_alfa(document.getElementById('cidadeaux').value, 0, 60, 'Cidade - residencia') &&
				validaformulario_sonumero(document.getElementById('fone').value, 10, 11, 'Fone') &&
				validaformulario_sonumero(document.getElementById('anofabricacao').value, 0, 4, 'Ano Fabricacao') &&
				validaformulario_sonumero(document.getElementById('celular').value, 0, 11, 'Celular') &&
				validaformulario_alfa(document.getElementById('sql').value, 2, 10, 'Sql')) {

				return true;

			} else {

				return false;

			}
		}

		//inicia a funcao que busca os dados do motorista no banco de dados ultilizando ajax
		function salva() { //deve ser passado o nome do campo em que esta o cpf onblur='(this)';


			//alert(document.getElementById('anofabricacao').value);

			/*
				if (document.getElementById('cep').value == '') {
					document.getElementById('cep').value = ''
				}	
					
					
						+'&endereco='+document.getElementById('endereco').value
						+'&uf='+document.getElementById('ufaux').value
						+'&cidade='+document.getElementById('cidadeaux').value
			*/
			if (valida()) {

				//define a varivel ajax como a conexao ajax  que foi feita 
				ajax1 = ajaxInit();
				//abre uma instancia ajax ultiliznado get e mandando para a funcao funajax.php

				ajax1.open('get', "funajax.php?&sq=gravaplaca" +
					"&cpfcnpj=" + document.getElementById('cpfcnpj').value +
					"&sqlplaca=" + document.getElementById('sqlplaca').value +
					'&codipessoa=' + document.getElementById('codipessoa').value +
					'&placa=' + document.getElementById('placa').value +
					'&ufplaca=' + document.getElementById('ufplaca').value +
					'&chassi=' + document.getElementById('chassi').value +
					'&renavan=' + document.getElementById('renavan').value +
					'&marca=' + document.getElementById('marca').value +
					'&modelo=' + document.getElementById('modelo').value +
					'&anofabricacao=' + document.getElementById('anofabricacao').value +
					'&cor=' + document.getElementById('cor').value +
					'&nomepessoa=' + document.getElementById('nomepessoa').value +
					'&cep=' + document.getElementById('cep').value +
					'&endereco=' + document.getElementById('endereco').value +
					'&uf=' + document.getElementById('ufaux').value +
					'&cidade=' + document.getElementById('cidadeaux').value +
					'&fone=' + document.getElementById('fone').value +
					'&celular=' + document.getElementById('celular').value +
					'&sql=' + document.getElementById('sql').value +
					'&tipopessoa=' + document.getElementById('tipopessoa').value +
					'&categoria=' + document.getElementById('categoria').value +
					'&numeroseguranca=' + document.getElementById('numeroseguranca').value +
					'&antt=' + document.getElementById('antt').value +
					'&tara=' + document.getElementById('tara').value +
					'&capacidadecargakg=' + document.getElementById('capacidadecargakg').value +
					'&capacidadecargam3=' + document.getElementById('capacidadecargam3').value +
					'&tipocarroceria=' + document.getElementById('tipocarroceria').value +
					'&cpfcnpjtransportador=' + document.getElementById('cpfcnpjtransportador').value +
					'&nometransportador=' + document.getElementById('nometransportador').value
				);

				//a cada vez que o estado mudar chama a funcao preencheoscampos
				ajax1.onreadystatechange = function() {

					if (ajax1.readyState == 4) {

						//define a variavel resposta como a resposta trazida pelo ajax
						resposta = ajax1.responseText;
						//alert(resposta);

						document.getElementById('avisos').innerHTML = resposta; //resposta; 

						//limpa o formulario para nao ficar sujeira se o retorno foi ok				
						if (resposta.length == 123) {

							zeracampospessoa();

							//document.getElementById('placa').focus();

							eliminacookie();

						}
						eliminacookie();
					}
				}
				//envia a solicitacao ajax
				ajax1.send(null);
				//zeracampospessoa();



			}
		}

		// zera campos necessario se foi cadatrado e depois um insert, tem que limpar campos
		function zeracampospessoa() {

			document.getElementById('sqlplaca').value = '';
			document.getElementById('placa').value = '';
			document.getElementById('ufplaca').value = 'Selecione';
			document.getElementById('chassi').value = '';
			document.getElementById('renavan').value = '';
			document.getElementById('marca').value = '';
			document.getElementById('modelo').value = '';
			document.getElementById('tara').value = '';
			document.getElementById('capacidadecargakg').value = '';
			document.getElementById('capacidadecargam3').value = '';
			document.getElementById('tipocarroceria').value = '';
			document.getElementById('anofabricacao').value = '';
			document.getElementById('cor').value = 'Selecione';
			document.getElementById('nomepessoa').value = '';
			document.getElementById('tipopessoa').value = '';
			document.getElementById('uf').value = 'Selecione';
			document.getElementById('cidade').value = 'Selecione';
			document.getElementById('ufaux').value = 'Selecione';
			document.getElementById('cidadeaux').value = 'Selecione';
			document.getElementById('fone').value = '';
			document.getElementById('cpfcnpj').value = '';
			document.getElementById('cor').value = 'Selecione';
			document.getElementById('celular').value = '';
			document.getElementById('categoria').value = '';
			document.getElementById('sql').value = '';
			document.getElementById('antt').value = '';
			document.getElementById('numeroseguranca').value = '';
			document.getElementById('cpfcnpjtransportador').value = '';
			document.getElementById('nometransportador').value = '';
			document.getElementById('placa').focus();
		}
	</script>