# 🌞 Widget de Vendas — Canal Solar

> Widget PHP integrado ao **Bitrix24 CRM** para seleção de itens de contratos publicitários, com geração automática de PDF via **n8n** e assinatura digital pelo **DocuSign**.

---

## 📋 Visão Geral

Este projeto é um widget embarcado no painel de informações do CRM Bitrix24. Ele permite que o time comercial selecione os produtos e serviços vendidos diretamente dentro do card do negócio, calculando o valor total automaticamente e salvando os dados para geração do contrato.

### Fluxo completo da automação

```
Bitrix24 (card) → Widget (seleção de itens) → n8n (gera PDF) → Bitrix Drive (armazena)
→ Revisão humana → n8n (envia ao DocuSign) → Cliente assina → Bitrix24 (card "Vendido")
```

---

## ✨ Funcionalidades

- ✅ Lista de produtos organizados por categoria (Site, Youtube, Instagram, Revista, Eventos, etc.)
- ✅ Seleção de múltiplos itens com controle de quantidade
- ✅ Cálculo do valor total em tempo real
- ✅ Atualização automática do campo **"Valor do negócio"** no Bitrix24
- ✅ Persistência dos itens selecionados no card (salvo em campo UF_CRM)
- ✅ Interface responsiva e integrada ao layout do Bitrix24
- ✅ Carrega automaticamente os itens já salvos ao reabrir o widget

---

## 🛠️ Tecnologias

| Tecnologia | Uso |
|---|---|
| PHP 8.x | Backend do widget |
| Bitrix24 REST API | Leitura e escrita no CRM |
| n8n | Automação de workflows |
| DocuSign API | Assinatura digital |
| html2pdf API | Geração de contratos PDF |
| Laravel Herd | Ambiente local de desenvolvimento |
| ngrok / Ploi | Exposição e hospedagem |

---

## 📁 Estrutura do Projeto

```
widget-vendas/
├── index.php        # Widget principal
├── .env.example     # Variáveis de ambiente necessárias
└── README.md        # Documentação
```

---

## ⚙️ Configuração

### 1. Clone o repositório

```bash
git clone https://github.com/viniciussilva-dev/widget-vendas.git
cd widget-vendas
```

### 2. Configure as variáveis de ambiente

Copie o arquivo de exemplo e preencha com suas credenciais:

```bash
cp .env.example .env
```

```env
BITRIX_WEBHOOK=https://seu-bitrix.bitrix24.com.br/rest/USER_ID/TOKEN/
CAMPO_ITENS=UF_CRM_XXXXXXXXXXXXXXXXX
```

### 3. Configure o servidor

Recomenda-se **Laravel Herd** para desenvolvimento local ou **Ploi** para produção.

Para testar localmente com ngrok:

```bash
ngrok http https://seu-site.test --host-header=seu-site.test
```

### 4. Registre o widget no Bitrix24

1. Acesse `https://seu-bitrix.bitrix24.com.br/devops/section/widget/`
2. Selecione **"Mostre uma informação personalizada no painel de informações do CRM"**
3. Preencha:
   - **Título:** `Itens do Contrato`
   - **URL do manipulador:** `https://sua-url.com/?deal_id={ID}`
   - **Área:** `CRM_DEAL_DETAIL_TAB`
4. Salve e abra qualquer card de negócio

---

## 🔄 Integração com n8n

Este widget faz parte de uma automação maior construída no **n8n**:

### Workflow 1A — `bitrix-gerar-contrato`
Trigger: card movido para etapa **"Gerar Contrato"**

```
Webhook → Busca dados do deal → Valida campos → Gera HTML do contrato
→ Converte para PDF → Upload no Bitrix Drive → Notifica no card
→ Move card para "Verificar Contrato"
```

### Workflow 1B — `bitrix-enviar-contrato`
Trigger: card movido para etapa **"Contrato Enviado"**

```
Webhook → Busca PDF no Drive → Baixa e converte para base64
→ Monta envelope DocuSign → Envia para assinatura
```

### Workflow 2 — `docusign-retorno-bitrix`
Trigger: webhook do DocuSign (envelope assinado)

```
Webhook → Extrai deal_id do envelope → Move card para "Vendido"
```

---

## 📦 Campos UF_CRM utilizados

| Campo | Descrição |
|---|---|
| `UF_CRM_1778855870978` | Itens do contrato (JSON) |
| `UF_CRM_1778853872449` | Representante Legal |
| `UF_CRM_1726075303537` | CNPJ do anunciante |
| `UF_CRM_1725369867228` | Endereço do anunciante |
| `UF_CRM_1749582133444` | Parcelas (ID mapeado) |
| `UF_CRM_1749582045688` | Data de pagamento |

---

## 🚀 Deploy em Produção (Ploi)

1. Crie um novo site no Ploi apontando para este repositório
2. Configure as variáveis de ambiente no painel do Ploi
3. Atualize a URL do widget no Bitrix24 para a URL de produção
4. Atualize os webhooks do n8n para URLs de produção

---

## 📸 Preview

![Widget funcionando dentro do card Bitrix24](<img width="1408" height="749" alt="Captura de tela 2026-05-15 152758" src="https://github.com/user-attachments/assets/e8b45fc8-cbaf-4b03-a5a8-72c2b37ea56a" />
)

---

## 👨‍💻 Autor

**Vinicius Silva**
- GitHub: [@viniciussilva-dev](https://github.com/viniciussilva-dev)
