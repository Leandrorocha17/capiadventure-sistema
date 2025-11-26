<?php
session_start();
// VERIFICAÇÃO DE ACESSO: Deve ser ADMIN logado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: login.html?acesso=negado");
    exit();
}

// Opções para ENUMs do banco de dados
$dificuldades = ['Baixo', 'Médio', 'Alto'];
$tipos_trilha = ['Caminhada', 'Ciclismo', 'Trekking', 'Corrida', 'Passeio', 'Outro']; // NOVO: Tipos de Trilha
$regioes = [
    'Brasília (Plano Piloto)', 'Gama', 'Taguatinga', 'Brazlândia', 'Sobradinho', 'Planaltina', 'Paranoá',
    'Núcleo Bandeirante', 'Ceilândia', 'Guará', 'Cruzeiro', 'Samambaia', 'Santa Maria', 'São Sebastião',
    'Recanto das Emas', 'Lago Sul', 'Riacho Fundo', 'Lago Norte', 'Candangolândia', 'Águas Claras',
    'Riacho Fundo II', 'Sudoeste/Octogonal', 'Varjão', 'Park Way', 'SCIA / Estrutural', 'Sobradinho II',
    'Jardim Botânico', 'Itapoã', 'SIA (Setor de Indústria e Abastecimento)', 'Vicente Pires', 'Fercal', 
    'Sol Nascente / Pôr do Sol', 'Arniqueira'
];
$dias_semana = ['seg', 'ter', 'qua', 'qui', 'sex', 'sab', 'dom'];
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>CAPIADVENTURE - Cadastrar Trilha</title>
  <link rel="stylesheet" href="style.css">
  <style>
        /* Configuração da Logo com Sombra Preta */
        .brand .logo {
            width: 80px; 
            height: 80px; 
            background-color: transparent;
            /* Propriedades para arredondar o círculo e aplicar a sombra */
            border-radius: 50%; /* Faz o elemento ser um círculo */
            /* Sombra preta com 70% de opacidade */
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.7);
        }

        .brand .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain; 
        }
      /* TORNAR O CARD DE CADASTRO MAIS LARGO */
      .cadastro-trilha-box {
          max-width: 700px; 
          width: 90%; 
      }
      
      /* Estilos ajustados para acomodar Entrada e Saída */
      .form-group-horario {
          display: flex;
          flex-wrap: wrap;
          gap: 10px;
          margin-bottom: 15px;
          padding: 10px;
          border: 1px solid #ddd;
          border-radius: 4px;
      }
      .horario-item {
          flex: 1 1 calc(50% - 10px); 
          display: flex; 
          flex-direction: column;
          border: 1px solid #eee;
          padding: 8px;
          border-radius: 4px;
      }
      .horario-item h4 {
          margin-top: 0;
          margin-bottom: 5px;
          font-size: 14px;
          color: #555;
      }
      .horario-campos {
          display: flex;
          gap: 5px;
      }
      .horario-campos label {
          font-weight: normal;
          font-size: 12px;
          color: #777;
          flex-basis: 50%;
          text-align: left;
      }
      .horario-campos input[type="time"] {
          width: 100%;
          padding: 6px;
          border: 1px solid #ccc;
          border-radius: 4px;
      }
      @media (min-width: 600px) {
          .horario-item {
              flex: 1 1 calc(33.33% - 10px);
          }
      }

      /* REGRAS DE BOTÕES (IGUALANDO ALTURA E ALINHAMENTO) - ATUALIZADAS E FORÇADAS */
      .form-actions {
          display: flex;
          justify-content: center; 
          gap: 15px; 
          margin-top: 20px;
          max-width: 400px; 
          margin-left: auto;
          margin-right: auto;
          align-items: stretch !important; /* Força que os filhos tenham a mesma altura */
      }
      .form-actions .btn, 
      .form-actions .btn-cancelar {
          flex: 1; 
          min-width: 150px; 
          
          /* Estilos de Aparência */
          padding: 10px 15px; 
          font-size: 16px;
          border-radius: 4px; 
          font-weight: bold; 
          border: none !important; 
          text-decoration: none !important; /* GARANTINDO que não haja sublinhado */
          /* transition: background-color 0.3s ease; REMOVIDO PARA TIRAR O MOVIMENTO SUAVE */
          
          /* ESTILOS DE CENTRALIZAÇÃO REFORÇADA: */
          display: flex !important; 
          align-items: center !important; /* Centraliza o texto verticalmente */
          justify-content: center !important; /* Centraliza o texto horizontalmente */
          height: 100% !important; 
          line-height: normal !important; 
          text-align: center !important; 
          margin: 0 !important; /* Remove margens externas que causavam desalinhamento */
          vertical-align: middle !important; 
      }
      
      .btn {
          background-color: #34B5CC; 
          color: white;
      }
      /* ATUALIZADO: Força a cor original, remove a decoração no HOVER para anular o sublinhado, transição/movimento e cursor de ponteiro */
      .btn:hover {
          background-color: #34B5CC !important; /* Volta para a cor original (Anula a mudança de cor) */
          text-decoration: none !important; 
          cursor: default !important; /* Remove o cursor de mão/ponteiro */
      }
      
      .btn-cancelar {
          background-color: #dc3545; 
          color: white;
      }
      /* ATUALIZADO: Força a cor original, remove a decoração no HOVER para anular o sublinhado, transição/movimento e cursor de ponteiro */
      .btn-cancelar:hover {
          background-color: #dc3545 !important; /* Volta para a cor original (Anula a mudança de cor) */
          text-decoration: none !important;
          cursor: default !important; /* Remove o cursor de mão/ponteiro */
      }
      
      /* ESTILO DO RODAPÉ (COPIADO DE GERENCIAR_TRILHAS.PHP) */
      .footer {
            background-color: black;
            color: white; 
            text-align: center; 
            padding: 15px 0;
            width: 100%;
            margin-top: 30px; /* Garante o espaçamento superior */
      }
      
      /* NOVO: Estilos para Links de Navegação (Menu Topo) - Para remover sublinhado e movimento de hover */
      .nav-links a {
          text-decoration: none !important; /* Força a remoção do sublinhado no estado normal */
      }
      .nav-links a:hover {
          text-decoration: none !important; /* Garante a remoção do sublinhado no hover */
          cursor: default !important; /* Remove o cursor de ponteiro no hover, removendo o "movimento" visual */
          /* Para remover o "movimento" (transição/mudança de cor),
             se houver, você pode precisar forçar a cor de volta para a cor normal aqui.
             Exemplo (se a cor normal for preta): color: black !important; 
          */
      }
      
      /* NOVO: Estilo para os títulos solicitados (Preto 50% Opacidade) */
      .cadastro-trilha-box h2,
      .cadastro-trilha-box h3 {
          color: rgba(0, 0, 0, 0.5); /* Preto com 50% de opacidade */
      }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="brand">
      <div class="logo">
        <img src="capivara.png" alt="Logotipo CAPIADVENTURE">
      </div>
      <div class="title">CAPIADVENTURE</div>
    </div>
    
    <nav class="nav-links" aria-label="Navegação administrativa">
        <ul>
            <li><a href="gerenciar_trilhas.php" class="top-link">Gerenciar minhas trilhas</a></li>
            <li><a href="gerenciar_agendamentos.php" class="top-link">Gerenciar agendamentos</a></li>
            <li><a href="meus_agendamentos.php" class="top-link">Meus aendamentos</a></li>
            <li><a href="trilhas.php" class="top-link">Trilhas</a></li>
            <li><a href="alterar_senha_logado.php" class="top-link">Alterar senha</a></li>
            <li><a href="logout.php" class="top-link">Sair</a></li>
        </ul>
    </nav>
  </header>

  <main class="main">
    <section class="screen" id="tela_cadastro_trilha" aria-labelledby="trilha-heading">
      <div class="login-box cadastro-trilha-box">
        
        <h2 id="trilha-heading">Cadastrar Nova Trilha</h2>

        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] == 'sucesso'): ?>
                <p class="mensagem-sucesso">Trilha cadastrada com sucesso!</p>
            <?php elseif ($_GET['status'] == 'erro'): ?>
                <p class="mensagem-erro">Erro: Falha ao cadastrar a trilha. Verifique os dados.</p>
            <?php endif; ?>
        <?php endif; ?>

        <form action="processa_cadastro_trilha.php" method="POST" enctype="multipart/form-data" autocomplete="off">
          
          <label for="nome" class="sr-only">Nome da Trilha:</label>
          <input id="nome" type="text" placeholder="Nome da Trilha" name="nome" required>

          <label for="descricao" class="sr-only">Descrição:</label>
          <textarea id="descricao" placeholder="Descrição detalhada da trilha" name="descricao" rows="4" required></textarea>

          <label for="imagens">Imagens da Trilha (Max 10 fotos, JPG/PNG - Max 2MB cada):</label>
          <input 
            id="imagens" 
            type="file" 
            name="imagens[]" 
            accept="image/jpeg,image/png" 
            multiple 
            required
            onchange="validarFotos(this)"
          >
          <label for="distancia" class="sr-only">Distância (km):</label>
          <input id="distancia" type="number" step="0.01" placeholder="Distância (Ex: 12.50 km)" name="distancia" required>

          <label for="tipo_trilha" class="sr-only">Tipo de Trilha:</label>
          <select id="tipo_trilha" name="tipo_trilha" required>
            <option value="">Selecione o Tipo de Trilha</option>
            <?php foreach ($tipos_trilha as $t): ?>
                <option value="<?php echo $t; ?>"><?php echo $t; ?></option>
            <?php endforeach; ?>
          </select>

          <label for="dificuldade" class="sr-only">Nível de Dificuldade:</label>
          <select id="dificuldade" name="nivel_dificuldade" required>
            <option value="">Selecione a Dificuldade</option>
            <?php foreach ($dificuldades as $d): ?>
                <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
            <?php endforeach; ?>
          </select>

          <label for="regiao" class="sr-only">Região:</label>
          <select id="regiao" name="regiao" required>
            <option value="">Selecione a Região</option>
            <?php foreach ($regioes as $r): ?>
                <option value="<?php echo $r; ?>"><?php echo $r; ?></option>
            <?php endforeach; ?>
          </select>

          <h3>Horários de Funcionamento</h3>
          <div class="form-group-horario">
              <?php foreach ($dias_semana as $dia): ?>
                  <div class="horario-item">
                      <h4><?php echo ucfirst($dia); ?>:</h4>
                      <div class="horario-campos">
                          <label>
                              Entrada:
                              <input type="time" name="hr_<?php echo $dia; ?>_entrada">
                          </label>
                          <label>
                              Saída:
                              <input type="time" name="hr_<?php echo $dia; ?>_saida">
                          </label>
                      </div>
                  </div>
              <?php endforeach; ?>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn">Cadastrar</button>
            <button type="button" class="btn-cancelar" onclick="cancelarCadastro()">Cancelar</button>
          </div>
        </form>
        
      </div>
    </section>
  </main>
  
  <footer class="footer">
    <p><?php echo date('Y'); ?> CAPIADVENTURE. Desenvolvido por Leandro Rocha & Gabriel Gonçalves.</p>
  </footer>

  <script>
      /**
       * Valida se o número de fotos selecionadas não excede o limite (10).
       */
      function validarFotos(input) {
          const maxFotos = 10;
          if (input.files.length > maxFotos) {
              alert(`Você pode selecionar no máximo ${maxFotos} fotos. Por favor, remova ${input.files.length - maxFotos} fotos.`);
              // Limpa a seleção (opcional, mas recomendado para forçar o usuário a re-selecionar)
              input.value = ''; 
          }
          // Nota: A validação de tipo e tamanho de cada arquivo deve ser feita principalmente no servidor (processa_cadastro_trilha.php)
      }

      /**
       * Redireciona o usuário para a tela de Gerenciar Trilhas ao cancelar.
       */
      function cancelarCadastro() {
          if (confirm("Tem certeza que deseja cancelar o cadastro? Todos os dados não salvos serão perdidos.")) {
              window.location.href = 'gerenciar_trilhas.php';
          }
      }
  </script>
</body>
</html>