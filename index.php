<?php
// ============================================================
// CONFIGURAÇÕES
// ============================================================
define('BITRIX_WEBHOOK', getenv('BITRIX_WEBHOOK') ?: 'https://seu-bitrix.bitrix24.com.br/rest/USER_ID/TOKEN/');
define('CAMPO_ITENS', getenv('CAMPO_ITENS') ?: 'UF_CRM_XXXXXXXXXXXXXXXXX');

// ============================================================
// PRODUTOS (Canal Solar 2026)
// ============================================================
$produtos = [
    'Site' => [
        ['nome' => 'Redação + divulgação nas redes sociais', 'valor' => 10600],
        ['nome' => 'Banner Horizontal Topo',                 'valor' => 28000],
        ['nome' => 'Banner Horizontal Meio',                 'valor' => 20000],
        ['nome' => 'Banner Horizontal Inferior',             'valor' => 15000],
        ['nome' => 'Banner Horizontal Rodapé',               'valor' => 10000],
        ['nome' => 'Banner Horizontal Notícias',             'valor' => 15000],
        ['nome' => 'Banner Horizontal Artigos',              'valor' =>  8000],
    ],
    'Youtube' => [
        ['nome' => 'Vídeo (Produzido)',    'valor' => 17000],
        ['nome' => 'Papo Solar Podcast',   'valor' => 15000],
        ['nome' => 'Papo Solar Publi',     'valor' =>  3500],
        ['nome' => 'Live (webinário)',      'valor' => 11000],
    ],
    'Instagram' => [
        ['nome' => 'Reels', 'valor' => 5500],
        ['nome' => 'Story',  'valor' => 3500],
    ],
    'Revista' => [
        ['nome' => 'Redação Revista',      'valor' => 10600],
        ['nome' => 'Anúncio Página Dupla', 'valor' => 12000],
        ['nome' => 'Anúncio Página Inteira','valor' =>  7500],
        ['nome' => 'Anúncio Meia Página',  'valor' =>  5000],
        ['nome' => 'Anúncio Rodapé',       'valor' =>  2500],
    ],
    'Eventos' => [
        ['nome' => 'Live',              'valor' => 20000],
        ['nome' => 'Cobertura',         'valor' => 15000],
        ['nome' => 'Palestra Sócio Sr', 'valor' => 25000],
        ['nome' => 'Palestra Sócio Jr', 'valor' => 18000],
        ['nome' => 'Palestra Equipe',   'valor' => 12000],
    ],
    'Extras' => [
        ['nome' => 'Ebook', 'valor' => 18000],
    ],
    'Conecta' => [
        ['nome' => 'Diamante', 'valor' => 60000],
        ['nome' => 'Ouro',     'valor' => 40000],
        ['nome' => 'Prata',    'valor' => 20000],
        ['nome' => 'Bronze',   'valor' => 10000],
    ],
    'Intersolar' => [
        ['nome' => 'Diamante', 'valor' => 34997],
        ['nome' => 'Ouro',     'valor' => 25997],
        ['nome' => 'Prata',    'valor' => 19997],
        ['nome' => 'Bronze',   'valor' => 14997],
    ],
];

// ============================================================
// SALVAR — recebe POST com deal_id e itens selecionados
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deal_id'])) {
    $dealId = intval($_POST['deal_id']);
    $itens  = json_decode($_POST['itens'] ?? '[]', true);

    if (!empty($itens) && $dealId > 0) {
        // Calcula valor total dos itens
        $valorTotal = 0;
        foreach ($itens as $item) {
            $valorTotal += floatval($item['valor_total'] ?? 0);
        }

        $payload = json_encode($itens, JSON_UNESCAPED_UNICODE);

        // Salva itens E atualiza o valor do negócio automaticamente
        $url  = BITRIX_WEBHOOK . 'crm.deal.update.json';
        $data = http_build_query([
            'id'                          => $dealId,
            'fields[' . CAMPO_ITENS . ']' => $payload,
            'fields[OPPORTUNITY]'         => $valorTotal,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($resp, true);
        echo json_encode([
            'ok'          => isset($result['result']) && $result['result'] === true,
            'valor_total' => $valorTotal,
            'bitrix'      => $result,
        ]);
    } else {
        echo json_encode(['ok' => false, 'msg' => 'Dados inválidos']);
    }
    exit;
}

// ============================================================
// CARREGAR deal_id — GET ou POST do Bitrix
// ============================================================
$dealId = intval($_GET['deal_id'] ?? 0);

if (!$dealId && isset($_POST['PLACEMENT_OPTIONS'])) {
    $opts   = json_decode($_POST['PLACEMENT_OPTIONS'], true);
    $dealId = intval($opts['ID'] ?? 0);
}

// ============================================================
// CARREGAR itens já salvos no deal
// ============================================================
$itensSalvos = [];

if ($dealId > 0) {
    $url  = BITRIX_WEBHOOK . 'crm.deal.get.json?id=' . $dealId;
    $resp = file_get_contents($url);
    $data = json_decode($resp, true);
    $raw  = $data['result'][CAMPO_ITENS] ?? '';
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $itensSalvos = $decoded;
        }
    }
}

// Monta mapa: "Canal|Nome" => quantidade
$mapaQtd = [];
foreach ($itensSalvos as $item) {
    $chave           = ($item['canal'] ?? '') . '|' . ($item['nome'] ?? '');
    $mapaQtd[$chave] = $item['quantidade'] ?? 1;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Itens do Contrato — Canal Solar</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    font-family: 'Segoe UI', system-ui, sans-serif;
    background: #f0f2f5;
    color: #1a1a2e;
    padding: 16px;
    font-size: 13px;
  }
  h1 {
    font-size: 15px;
    font-weight: 700;
    color: #e8790a;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  h1::before {
    content: '';
    display: inline-block;
    width: 4px;
    height: 18px;
    background: #e8790a;
    border-radius: 2px;
  }
  .categoria {
    background: #fff;
    border-radius: 10px;
    margin-bottom: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    overflow: hidden;
  }
  .categoria-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: #1a1a2e;
    color: #fff;
    font-weight: 600;
    font-size: 12px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    cursor: pointer;
    user-select: none;
  }
  .categoria-header .arrow { margin-left: auto; transition: transform 0.2s; font-size: 10px; }
  .categoria-header.collapsed .arrow { transform: rotate(-90deg); }
  .categoria-body { padding: 8px 0; }
  .categoria-body.hidden { display: none; }
  .produto-row {
    display: flex;
    align-items: center;
    padding: 7px 14px;
    gap: 10px;
    transition: background 0.15s;
  }
  .produto-row:hover { background: #f8f9ff; }
  .produto-row input[type="checkbox"] {
    width: 15px; height: 15px;
    accent-color: #e8790a;
    cursor: pointer; flex-shrink: 0;
  }
  .produto-nome { flex: 1; cursor: pointer; }
  .produto-valor { color: #555; font-size: 11px; white-space: nowrap; min-width: 90px; text-align: right; }
  .qtd-wrap { display: flex; align-items: center; gap: 4px; }
  .qtd-wrap button {
    width: 22px; height: 22px;
    border: 1px solid #ddd; border-radius: 4px;
    background: #f5f5f5; cursor: pointer;
    font-size: 13px; line-height: 1;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.15s;
  }
  .qtd-wrap button:hover { background: #e8790a; color: #fff; border-color: #e8790a; }
  .qtd-input {
    width: 36px; text-align: center;
    border: 1px solid #ddd; border-radius: 4px;
    padding: 3px 4px; font-size: 12px; background: #fff;
  }
  .qtd-input:disabled { background: #f0f0f0; color: #aaa; }
  .footer {
    position: sticky; bottom: 0;
    background: #fff;
    border-top: 2px solid #e8790a;
    padding: 12px 16px;
    margin: 0 -16px -16px;
    display: flex; align-items: center;
    justify-content: space-between; gap: 12px;
    box-shadow: 0 -4px 12px rgba(0,0,0,0.1);
  }
  .total-label { font-size: 12px; color: #666; }
  .total-valor { font-size: 16px; font-weight: 700; color: #1a1a2e; }
  .total-hint { font-size: 10px; color: #e8790a; margin-top: 2px; }
  .btn-salvar {
    background: #e8790a; color: #fff; border: none;
    padding: 10px 24px; border-radius: 8px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    transition: background 0.2s, transform 0.1s;
  }
  .btn-salvar:hover { background: #cf6b09; }
  .btn-salvar:active { transform: scale(0.97); }
  .btn-salvar:disabled { background: #ccc; cursor: not-allowed; }
  .toast {
    position: fixed; top: 16px; right: 16px;
    padding: 10px 18px; border-radius: 8px;
    font-size: 13px; font-weight: 600; z-index: 9999;
    opacity: 0; transform: translateY(-8px);
    transition: all 0.3s; pointer-events: none;
  }
  .toast.show { opacity: 1; transform: translateY(0); }
  .toast.ok  { background: #22c55e; color: #fff; }
  .toast.err { background: #ef4444; color: #fff; }
  .empty-msg { text-align: center; padding: 30px; color: #aaa; font-size: 13px; }
</style>
</head>
<body>

<?php if ($dealId <= 0): ?>
  <div class="empty-msg">⚠️ Nenhum negócio identificado.<br>Abra este widget a partir de um card do Bitrix24.</div>
<?php else: ?>

<h1>Itens do Contrato</h1>

<form id="form-itens">
  <input type="hidden" name="deal_id" value="<?= $dealId ?>">

  <?php foreach ($produtos as $canal => $itens): ?>
  <div class="categoria">
    <div class="categoria-header" onclick="toggleCategoria(this)">
      <?= htmlspecialchars($canal) ?>
      <span class="arrow">▼</span>
    </div>
    <div class="categoria-body">
      <?php foreach ($itens as $p):
        $chave    = $canal . '|' . $p['nome'];
        $checked  = isset($mapaQtd[$chave]);
        $qtd      = $mapaQtd[$chave] ?? 1;
        $idCheck  = 'chk_' . md5($chave);
        $valorFmt = 'R$ ' . number_format($p['valor'], 0, ',', '.');
      ?>
      <div class="produto-row">
        <input
          type="checkbox"
          id="<?= $idCheck ?>"
          data-canal="<?= htmlspecialchars($canal) ?>"
          data-nome="<?= htmlspecialchars($p['nome']) ?>"
          data-valor="<?= $p['valor'] ?>"
          onchange="onCheck(this)"
          <?= $checked ? 'checked' : '' ?>
        >
        <label class="produto-nome" for="<?= $idCheck ?>"><?= htmlspecialchars($p['nome']) ?></label>
        <span class="produto-valor"><?= $valorFmt ?></span>
        <div class="qtd-wrap">
          <button type="button" onclick="ajustarQtd(this, -1)" <?= !$checked ? 'disabled' : '' ?>>−</button>
          <input type="number" class="qtd-input" value="<?= $qtd ?>" min="1" max="99"
            onchange="calcTotal()" <?= !$checked ? 'disabled' : '' ?>>
          <button type="button" onclick="ajustarQtd(this, 1)" <?= !$checked ? 'disabled' : '' ?>>+</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</form>

<div class="footer">
  <div>
    <div class="total-label">Total selecionado</div>
    <div class="total-valor" id="total-display">R$ 0</div>
    <div class="total-hint" id="total-hint" style="display:none">✓ Valor do negócio será atualizado automaticamente</div>
  </div>
  <button class="btn-salvar" id="btn-salvar" onclick="salvar()">💾 Salvar Itens</button>
</div>

<div class="toast" id="toast"></div>

<script>
const DEAL_ID = <?= $dealId ?>;

function calcTotal() {
  let total = 0;
  document.querySelectorAll('input[type="checkbox"]:checked').forEach(chk => {
    const valor = parseFloat(chk.dataset.valor) || 0;
    const qtd = parseInt(chk.closest('.produto-row').querySelector('.qtd-input').value) || 1;
    total += valor * qtd;
  });
  document.getElementById('total-display').textContent =
    'R$ ' + total.toLocaleString('pt-BR', {minimumFractionDigits: 0});
  document.getElementById('total-hint').style.display = total > 0 ? 'block' : 'none';
  return total;
}

function onCheck(chk) {
  const row = chk.closest('.produto-row');
  const qtdInput = row.querySelector('.qtd-input');
  const btns = row.querySelectorAll('button');
  qtdInput.disabled = !chk.checked;
  btns.forEach(b => b.disabled = !chk.checked);
  if (chk.checked && parseInt(qtdInput.value) < 1) qtdInput.value = 1;
  calcTotal();
}

function ajustarQtd(btn, delta) {
  const input = btn.closest('.qtd-wrap').querySelector('.qtd-input');
  input.value = Math.max(1, Math.min(99, (parseInt(input.value) || 1) + delta));
  calcTotal();
}

function toggleCategoria(header) {
  header.classList.toggle('collapsed');
  header.nextElementSibling.classList.toggle('hidden');
}

async function salvar() {
  const btn = document.getElementById('btn-salvar');
  btn.disabled = true;
  btn.textContent = 'Salvando...';

  const itens = [];
  document.querySelectorAll('input[type="checkbox"]:checked').forEach(chk => {
    const row = chk.closest('.produto-row');
    const qtd = parseInt(row.querySelector('.qtd-input').value) || 1;
    const valor = parseFloat(chk.dataset.valor) || 0;
    itens.push({
      canal: chk.dataset.canal,
      nome: chk.dataset.nome,
      quantidade: qtd,
      valor_unit: valor,
      valor_total: valor * qtd,
    });
  });

  const form = new FormData();
  form.append('deal_id', DEAL_ID);
  form.append('itens', JSON.stringify(itens));

  try {
    const resp = await fetch(window.location.href, { method: 'POST', body: form });
    const data = await resp.json();
    if (data.ok) {
      const valorFmt = 'R$ ' + Number(data.valor_total).toLocaleString('pt-BR', {minimumFractionDigits: 0});
      showToast('✅ Itens salvos! Valor do negócio atualizado: ' + valorFmt, 'ok');
    } else {
      showToast('❌ Erro ao salvar. Tente novamente.', 'err');
    }
  } catch(e) {
    showToast('❌ Erro de conexão.', 'err');
  }

  btn.disabled = false;
  btn.textContent = '💾 Salvar Itens';
}

function showToast(msg, tipo) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast ' + tipo + ' show';
  setTimeout(() => t.classList.remove('show'), 4000);
}

calcTotal();
</script>

<?php endif; ?>
</body>
</html>