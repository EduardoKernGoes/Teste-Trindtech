import { useState } from 'react';
import api from '../../services/server';
import './Login.css'

export default function Login() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setLoading(true);

        try {
            const response = await api.get('/');

            // No Axios, os dados vêm em response.data (e já são JSON)
            console.log('Resposta completa do Axios:', response);
            console.log('Dados da API:', response.data);

            // Exemplo de alerta temporário para ver funcionando
            alert(response.data.message);

        } catch (err) {
            setError(err.message || 'Credenciais inválidas. Verifique os dados.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="login-container">
            <div className="login-card">
                <h2>Painel de Indicadores</h2>
                <p className="login-subtitle">Faça login para acessar os dados do IPCA e IGP-M</p>

                {error && <div className="login-error">{error}</div>}

                <form onSubmit={handleSubmit} className="login-form">
                    <div className="form-group">
                        <label htmlFor="email">E-mail</label>
                        <input
                        id="email"
                        type="email"
                        required
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        placeholder="admin@trindtech.com"
                        />
                    </div>

                    <div className="form-group">
                        <label htmlFor="password">Senha</label>
                        <input
                        id="password"
                        type="password"
                        required
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        placeholder="••••••••"
                        />
                    </div>

                    <button type="submit" className="login-button" disabled={loading}>
                        {loading ? 'Entrando...' : 'Entrar no Painel'}
                    </button>
                </form>

                <div className="login-footer">
                    Trindtech • Teste Técnico Desenvolvedor PHP Júnior
                </div>
            </div>
        </div>
    );
}