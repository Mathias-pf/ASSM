<?php
/**
 * Credenciais das contas de administração (direção) da área interna.
 * Estas contas veem todas as escolas e enviam mensagens para elas.
 * Senha em texto puro NUNCA fica aqui — apenas o hash (password_hash).
 *
 * Para mudar a senha:
 *   php -r "echo password_hash('nova-senha', PASSWORD_BCRYPT, ['cost' => 12]);"
 */
return [
    'diretor' => [
        'nome' => 'Direção',
        'senha_hash' => '$2y$12$IV1vbl1b4TJ.TyisiVi6/OpooXH/l//PnO2OAJWq3cSeyZpYgwVRa',
    ],
];
