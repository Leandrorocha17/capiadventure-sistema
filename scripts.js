/* scripts.js */

/**
 * NOVO: Função robusta para validação de CPF (incluindo dígitos verificadores)
 * Fonte: Receita Federal (implementação JS comum)
 */
function validarCPF(cpf) {
    cpf = cpf.replace(/[^\d]+/g, ''); // Remove caracteres não numéricos
    if (cpf == '') return false;
    
    // Elimina CPFs invalidos conhecidos
    if (cpf.length != 11 ||
        cpf == "00000000000" ||
        cpf == "11111111111" ||
        cpf == "22222222222" ||
        cpf == "33333333333" ||
        cpf == "44444444444" ||
        cpf == "55555555555" ||
        cpf == "66666666666" ||
        cpf == "77777777777" ||
        cpf == "88888888888" ||
        cpf == "99999999999")
        return false;
    
    // Valida 1o digito
    let add = 0;
    for (let i = 0; i < 9; i++)
        add += parseInt(cpf.charAt(i)) * (10 - i);
    let rev = 11 - (add % 11);
    if (rev == 10 || rev == 11)
        rev = 0;
    if (rev != parseInt(cpf.charAt(9)))
        return false;
    
    // Valida 2o digito
    add = 0;
    for (let i = 0; i < 10; i++)
        add += parseInt(cpf.charAt(i)) * (11 - i);
    rev = 11 - (add % 11);
    if (rev == 10 || rev == 11)
        rev = 0;
    if (rev != parseInt(cpf.charAt(10)))
        return false;
    
    return true;
}

/**
 * Formata o CPF para o padrão 000.000.000-00.
 */
function formatarCPF(cpf) {
    // Remove tudo que não for dígito
    cpf = cpf.replace(/\D/g, '');
    
    // Aplica a máscara se tiver 11 dígitos
    if (cpf.length === 11) {
        return cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }
    return cpf; // Retorna sem formatação se for inválido
}


/**
 * Função principal de validação de formulário do lado do cliente (frontend)
 */
function validarCadastro(event) {
    const nome = document.getElementById('nome-completo').value;
    const email = document.getElementById('email-cadastro').value;
    const cpfInput = document.getElementById('cpf');
    const cpf = cpfInput.value;
    const nascimento = document.getElementById('nascimento').value;

    // 1. Validação de Nome e Sobrenome (pelo menos duas palavras)
    if (!validarNomeSobrenome(nome)) {
        alert("Por favor, forneça nome e sobrenome.");
        event.preventDefault();
        return false;
    }

    // 2. Validação de Maioridade (18 anos)
    if (!validarMaioridade(nascimento)) {
        alert("Você deve ser maior de 18 anos para se cadastrar.");
        event.preventDefault();
        return false;
    }
    
    // 3. Validação robusta de CPF
    if (!validarCPF(cpf)) {
        alert("Erro: Por favor, digite um CPF válido.");
        event.preventDefault();
        return false;
    }

    // 4. Formatação de CPF (apenas para exibição no campo e envio)
    cpfInput.value = formatarCPF(cpf);

    // Validação de Email (opcional, pois o input type="email" faz o básico)
    if (!validarEmail(email)) {
        alert("Por favor, digite um e-mail válido.");
        event.preventDefault();
        return false;
    }
    
    return true; // Envia o formulário
}

/**
 * Verifica se a string tem pelo menos um espaço (nome e sobrenome).
 */
function validarNomeSobrenome(nome) {
    return nome.trim().includes(' ');
}

/**
 * Verifica se a data de nascimento indica maioridade (18 anos).
 */
function validarMaioridade(dataNascimento) {
    if (!dataNascimento) return false;
    const hoje = new Date();
    const dataNasc = new Date(dataNascimento);
    let idade = hoje.getFullYear() - dataNasc.getFullYear();
    const m = hoje.getMonth() - dataNasc.getMonth();
    
    if (m < 0 || (m === 0 && hoje.getDate() < dataNasc.getDate())) {
        idade--;
    }
    return idade >= 18;
}

/**
 * Validação básica de email.
 */
function validarEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}

// **Melhoria UX:** Aplica a formatação do CPF em tempo real (on blur)
document.addEventListener('DOMContentLoaded', () => {
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('blur', (e) => {
            e.target.value = formatarCPF(e.target.value);
        });
    }
});