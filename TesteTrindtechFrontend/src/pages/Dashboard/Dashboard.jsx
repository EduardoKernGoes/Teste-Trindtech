import { useState, useEffect } from 'react';
import { useAuth } from '../../context/AuthContext.jsx';
import { useNavigate } from 'react-router-dom';
import api from '../../services/server';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import './Dashboard.css';

function getErrorMessage(error, fallback) {
  const responseData = error.response?.data;

  if (typeof responseData?.message === 'string') {
    return responseData.message;
  }

  if (responseData?.errors && typeof responseData.errors === 'object') {
    const validationMessages = Object.values(responseData.errors)
      .flat()
      .filter((message) => typeof message === 'string');

    if (validationMessages.length > 0) {
      return validationMessages.join(' ');
    }
  }

  if (!error.response) {
    return 'Não foi possível conectar à API. Verifique sua conexão e tente novamente.';
  }

  return fallback;
}

export default function Dashboard() {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const [dadosIndicadores, setDadosIndicadores] = useState([]);
  const [opcoesIndicadores, setOpcoesIndicadores] = useState([]);

  const [loading, setLoading] = useState(true);
  const [syncing, setSyncing] = useState(false);
  const [error, setError] = useState('');
  const [successMessage, setSuccessMessage] = useState('');

  const [filtroIndicador, setFiltroIndicador] = useState('');
  const [dataInicial, setDataInicial] = useState('');
  const [dataFinal, setDataFinal] = useState('');

  const refreshDashboard = async () => {
    const [indicadoresResponse, opcoesResponse] = await Promise.all([
      api.get('/indicadores'),
      api.get('/indicadores/opcoes'),
    ]);

    setDadosIndicadores(indicadoresResponse.data);
    setOpcoesIndicadores(opcoesResponse.data);
  };

  useEffect(() => {
    let active = true;

    async function loadDashboard() {
      try {
        const [indicadoresResponse, opcoesResponse] = await Promise.all([
          api.get('/indicadores'),
          api.get('/indicadores/opcoes'),
        ]);

        if (active) {
          setDadosIndicadores(indicadoresResponse.data);
          setOpcoesIndicadores(opcoesResponse.data);
        }
      } catch (err) {
        console.error(err);

        if (active) {
          setError(getErrorMessage(err, 'Erro ao carregar os indicadores.'));
        }
      } finally {
        if (active) {
          setLoading(false);
        }
      }
    }

    loadDashboard();

    return () => {
      active = false;
    };
  }, []);

  const handleFilterSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const params = {};
      if (filtroIndicador) params.codigo_sgs = filtroIndicador;
      if (dataInicial) params.data_inicial = dataInicial;
      if (dataFinal) params.data_final = dataFinal;

      const response = await api.get('/indicadores/filtrar', { params });
      setDadosIndicadores(response.data);
    } catch (err) {
      setError(getErrorMessage(err, 'Erro ao aplicar filtros.'));
    } finally {
      setLoading(false);
    }
  };

  const handleClearFilters = () => {
    setFiltroIndicador('');
    setDataInicial('');
    setDataFinal('');
    setLoading(true);
    setError('');

    refreshDashboard()
      .catch((err) => {
        setError(getErrorMessage(err, 'Erro ao carregar os indicadores.'));
      })
      .finally(() => {
        setLoading(false);
      });
  };

  const handleSincronizar = async () => {
    setSyncing(true);
    setError('');
    setSuccessMessage('');
    try {
      await api.post('/indicadores/sincronizar', null, { timeout: 40000 });
      try {
        await refreshDashboard();
      } catch (err) {
        setError(`Sincronização concluída, mas não foi possível atualizar a tela. ${getErrorMessage(err, 'Tente recarregar a página.')}`);
        return;
      }

      setSuccessMessage('Dados sincronizados com sucesso com o Banco Central!');
    } catch (err) {
      setError(getErrorMessage(err, 'Erro ao sincronizar com a API do Banco Central.'));
    } finally {
      setSyncing(false);
    }
  };

  const handleDelete = async (codigo_sgs, nome) => {
    if (!window.confirm(`Tem certeza que deseja excluir o indicador "${nome}" e todo o seu histórico?`)) return;

    try {
      await api.delete(`/indicadores/${codigo_sgs}`);
      try {
        await refreshDashboard();
      } catch (err) {
        setError(`Indicador excluído, mas não foi possível atualizar a tela. ${getErrorMessage(err, 'Tente recarregar a página.')}`);
        return;
      }

      setSuccessMessage(`Indicador ${nome} excluído com sucesso.`);
    } catch (err) {
      setError(getErrorMessage(err, 'Erro ao excluir o indicador.'));
    }
  };

  const handleEditName = async (codigo_sgs, nomeAtual) => {
    const novoNome = prompt('Digite o novo nome para o indicador:', nomeAtual);
    if (!novoNome || novoNome.trim() === '' || novoNome === nomeAtual) return;

    try {
      await api.put(`/indicadores/${codigo_sgs}`, { nome: novoNome });
      try {
        await refreshDashboard();
      } catch (err) {
        setError(`Nome atualizado, mas não foi possível atualizar a tela. ${getErrorMessage(err, 'Tente recarregar a página.')}`);
        return;
      }

      setSuccessMessage('Nome atualizado com sucesso!');
    } catch (err) {
      setError(getErrorMessage(err, 'Erro ao atualizar o nome.'));
    }
  };

  const handleLogout = async () => { await logout(); navigate('/'); };

  return (
    <div className="dashboard-container">
      <header className="dashboard-header">
        <div className="header-title">
          <h1>Painel Econômico</h1>
          <p>Monitoramento de IPCA e IGP-M (SGS / Banco Central)</p>
        </div>
        <div className="header-user">
          <span>Olá, {user?.name || 'Admin'}</span>
          <button onClick={handleLogout} className="logout-button">Sair</button>
        </div>
      </header>

      <main className="dashboard-main">
        {error && <div className="alert-error">{error}</div>}
        {successMessage && <div className="alert-success">{successMessage}</div>}

        <div className="dashboard-actions">
          <button 
            onClick={handleSincronizar} 
            className="sync-button" 
            disabled={syncing}
          >
            {syncing ? 'Sincronizando...' : 'Sincronizar com Banco Central'}
          </button>
        </div>

        <section className="filter-section">
          <h2>Filtrar Consulta</h2>
          <form onSubmit={handleFilterSubmit} className="filter-form">
            <div className="filter-group">
              <label>Indicador</label>
              <select 
                value={filtroIndicador} 
                onChange={(e) => setFiltroIndicador(e.target.value)}
              >
                <option value="">Todos os Indicadores</option>
                {opcoesIndicadores.map((op) => (
                  <option key={op.codigo_sgs} value={op.codigo_sgs}>
                    {op.nome} (Cód: {op.codigo_sgs})
                  </option>
                ))}
              </select>
            </div>

            <div className="filter-group">
              <label>Data Inicial</label>
              <input 
                type="date" 
                value={dataInicial} 
                onChange={(e) => setDataInicial(e.target.value)} 
              />
            </div>

            <div className="filter-group">
              <label>Data Final</label>
              <input 
                type="date" 
                value={dataFinal} 
                onChange={(e) => setDataFinal(e.target.value)} 
              />
            </div>

            <div className="filter-buttons">
              <button type="submit" className="btn-filter">Filtrar</button>
              <button type="button" onClick={handleClearFilters} className="btn-clear">Limpar</button>
            </div>
          </form>
        </section>

        <section className="indicators-section">
          <h2>Séries Históricas (Tabela e Gráfico)</h2>
          {loading ? (
            <p className="loading-text">Carregando dados...</p>
          ) : (
            <div className="indicators-grid-tables">
              {dadosIndicadores.length > 0 ? (
                dadosIndicadores.map((ind) => (
                  <div key={ind.codigo_sgs} className="indicator-table-card">
                    <div className="row-header">
                      <div className="row-title">
                        <h3>{ind.nome}</h3>
                        <span className="sgs-tag">Código: {ind.codigo_sgs}</span>
                      </div>
                      <div className="row-actions">
                        <button 
                          onClick={() => handleEditName(ind.codigo_sgs, ind.nome)}
                          className="btn-edit"
                        >
                          Editar
                        </button>
                        <button 
                          onClick={() => handleDelete(ind.codigo_sgs, ind.nome)}
                          className="btn-delete"
                        >
                          Excluir
                        </button>
                      </div>
                    </div>

                    <div className="table-responsive">
                      <table className="indicator-table">
                        <thead>
                          <tr>
                            <th>Data</th>
                            <th>Valor (%)</th>
                          </tr>
                        </thead>
                        <tbody>
                          {ind.dados.length > 0 ? (
                            ind.dados.map((d) => (
                              <tr key={d.id}>
                                <td>{d.data}</td>
                                <td><strong>{d.valor}%</strong></td>
                              </tr>
                            ))
                          ) : (
                            <tr>
                              <td colSpan="2" className="no-data-text">Nenhum dado encontrado.</td>
                            </tr>
                          )}
                        </tbody>
                      </table>
                    </div>

                    <div className="chart-container">
                      <h4>Evolução Temporal</h4>
                      {ind.dados.length > 0 ? (
                        <ResponsiveContainer width="100%" height={220}>
                          <LineChart data={ind.dados} margin={{ top: 10, right: 10, left: -20, bottom: 0 }}>
                            <CartesianGrid strokeDasharray="3 3" stroke="#334155" />
                            <XAxis dataKey="data" stroke="#94a3b8" fontSize={12} />
                            <YAxis stroke="#94a3b8" fontSize={12} />
                            <Tooltip 
                              contentStyle={{ backgroundColor: '#0f172a', borderColor: '#334155', borderRadius: '6px', color: '#f8fafc' }} 
                            />
                            <Line 
                              type="monotone" 
                              dataKey="valor" 
                              stroke="#3b82f6" 
                              strokeWidth={2} 
                              dot={{ r: 3, fill: '#3b82f6' }} 
                              activeDot={{ r: 6 }} 
                            />
                          </LineChart>
                        </ResponsiveContainer>
                      ) : (
                        <p className="no-data-text">Sem dados para o gráfico.</p>
                      )}
                    </div>

                  </div>
                ))
              ) : (
                <p className="loading-text">Nenhum indicador encontrado com os filtros selecionados.</p>
              )}
            </div>
          )}
        </section>
      </main>
    </div>
  );
}