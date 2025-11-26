 // Alternar entre abas
        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                
                button.classList.add('active');
                const tabId = button.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');
            });
        });

        // Funções para máscaras de entrada
        function mascTelefone(input) {
            let value = input.value.replace(/\D/g, '');
            value = value.substring(0, 11);

            if (value.length > 6) {
                value = value.replace(/^(\d{2})(\d{5})(\d{0,4})/, '($1) $2-$3');
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
            } else {
                value = value.replace(/^(\d*)/, '($1');
            }

            input.value = value;
        }

        function aplicarMascara(input) {
            let valor = input.value.replace(/\D/g, '');

            if (valor.length > 10) {
                valor = valor.substring(0, 6);
            }

            if (valor.length <= 2) {
                valor = valor.replace(/(\d{2})/, '$1');
            } else if (valor.length <= 4) {
                valor = valor.replace(/(\d{2})(\d{2})/, '$1/$2');
            } else {
                valor = valor.replace(/(\d{2})(\d{2})(\d{4})/, '$1/$2/$3');
            }

            input.value = valor;
        }

        // Validação de senha
        function validarSenha() {
            const novaSenha = document.getElementById('new_password').value;
            const confirmSenha = document.getElementById('confirm_password').value;
            
            // Verificar se as senhas coincidem
            if (novaSenha !== confirmSenha) {
                alert('As senhas não coincidem!');
                return false;
            }
            
            // Verificar força da senha
            const regex = /^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*]).{8,}$/;
            if (!regex.test(novaSenha)) {
                alert('A senha deve conter pelo menos 8 caracteres, incluindo 1 letra maiúscula, 1 número e 1 caractere especial.');
                return false;
            }
            
            return true;
        }

        // Funções para o modal de exclusão
        function confirmDelete() {
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        }

        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const icon = passwordField.parentNode.querySelector('.toggle-password i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        document.getElementById('fotoUsuario').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.avatar').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Theme Switcher Functions
        function toggleThemeMenu() {
            const menu = document.getElementById('themeMenu');
            menu.classList.toggle('show');
        }

        function setTheme(theme) {
            // Close menu
            document.getElementById('themeMenu').classList.remove('show');
            
            // Set theme
            if (theme === 'system') {
                localStorage.removeItem('theme');
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.removeAttribute('data-theme');
                }
            } else {
                localStorage.setItem('theme', theme);
                document.documentElement.setAttribute('data-theme', theme);
            }
        }

        // Initialize theme
        function initTheme() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                setTheme(savedTheme);
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
            
            // Watch for system theme changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
                if (!localStorage.getItem('theme')) {
                    setTheme('system');
                }
            });
        }

        // Initialize on load
        window.addEventListener('DOMContentLoaded', initTheme);