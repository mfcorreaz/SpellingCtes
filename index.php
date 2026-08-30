<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spelling Bee Tournament System</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bee: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            400: '#facc15',
                            500: '#eab308',
                            600: '#ca8a04',
                            900: '#713f12',
                            dark: '#121316',
                            card: '#1e2026'
                        }
                    }
                }
            }
        }
    </script>

    <!-- FontAwesome & Google Fonts -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <!-- React and Babel CDNs -->
    <script src="https://unpkg.com/react@18/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #0d0e12;
            color: #f3f4f6;
        }
        .font-mono-timer {
            font-family: 'JetBrains Mono', monospace;
        }
        .glow-bee {
            box-shadow: 0 0 25px -5px rgba(234, 179, 8, 0.25);
        }
        .glow-red {
            box-shadow: 0 0 25px -5px rgba(239, 68, 68, 0.3);
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #181920;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #374151;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen flex flex-col selection:bg-yellow-500 selection:text-black">
    <div id="root"></div>

    <script type="text/babel">
        const { useState, useEffect, useRef, useMemo } = React;

        // Mock Initial Dataset
        const INITIAL_SCHOOLS = [
            { id: 'sch_1', name: 'Instituto Manuel Belgrano', code: 'IMB-01', city: 'Corrientes' },
            { id: 'sch_2', name: 'Colegio San José', code: 'CSJ-02', city: 'Resistencia' },
            { id: 'sch_3', name: 'Escuela Técnica Nº 1', code: 'ET1-03', city: 'Corrientes' }
        ];

        const INITIAL_TEACHERS = [
            { id: 'tch_1', name: 'Prof. Laura Benítez', dni: '28456123', schoolId: 'sch_1', email: 'lbenitez@belgrano.edu.ar' },
            { id: 'tch_2', name: 'Prof. Roberto Gómez', dni: '25987412', schoolId: 'sch_2', email: 'rgomez@sanjose.edu.ar' },
            { id: 'tch_3', name: 'Prof. Mariana Rossi', dni: '31223344', schoolId: 'sch_3', email: 'mrossi@tecnica1.edu.ar' }
        ];

        // Level Helper according to year rules
        const getLevelForYear = (year) => {
            const yr = parseInt(year);
            if (yr >= 1 && yr <= 3) return { level: 1, label: 'Level 1 (1º - 3º Año)' };
            if (yr >= 4 && yr <= 5) return { level: 2, label: 'Level 2 (4º - 5º Año)' };
            if (yr >= 6 && yr <= 7) return { level: 3, label: 'Level 3 (6º - 7º Año)' };
            return { level: 1, label: 'Level 1' };
        };

        const INITIAL_STUDENTS = [
            { id: 'std_1', dni: '48123456', name: 'Juan Pérez', year: 7, level: 3, schoolId: 'sch_1', teacherId: 'tch_1', division: 'A' },
            { id: 'std_2', dni: '47987654', name: 'Valentina Fernández', year: 3, level: 1, schoolId: 'sch_1', teacherId: 'tch_1', division: 'B' },
            { id: 'std_3', dni: '45111222', name: 'Mateo Rodríguez', year: 4, level: 2, schoolId: 'sch_2', teacherId: 'tch_2', division: 'A' },
            { id: 'std_4', dni: '44333444', name: 'Camila González', year: 5, level: 2, schoolId: 'sch_2', teacherId: 'tch_2', division: 'A' },
            { id: 'std_5', dni: '42555666', name: 'Lucas Silva', year: 6, level: 3, schoolId: 'sch_3', teacherId: 'tch_3', division: '1º' },
            { id: 'std_6', dni: '41777888', name: 'Sofia Romero', year: 7, level: 3, schoolId: 'sch_3', teacherId: 'tch_3', division: '2º' }
        ];

        const INITIAL_TOURNAMENTS = [
            {
                id: 'tr_1',
                name: 'CONCURSO SPELLING BEE 2026',
                type: 'inter_school',
                roundsCount: 3,
                maxAdvancingPerRound: 3,
                activeLevel: 3,
                activeRound: 1,
                participantIds: ['std_1', 'std_2', 'std_3', 'std_4', 'std_5', 'std_6'],
                status: 'active'
            }
        ];

        const WORD_BANK = [
            { level: 1, word: 'ACHIEVEMENT', sentence: 'Graduating high school is a great achievement.' },
            { level: 1, word: 'BEAUTIFUL', sentence: 'We watched a beautiful sunset over the river.' },
            { level: 2, word: 'PERSEVERANCE', sentence: 'Her perseverance led her to win the trophy.' },
            { level: 3, word: 'PARLIAMENTARY', sentence: 'The debate followed official parliamentary rules.' }
        ];

        function App() {
            const [currentTab, setCurrentTab] = useState('live'); // live, tournaments, students, teachers, schools, words, results
            const [schools, setSchools] = useState(INITIAL_SCHOOLS);
            const [teachers, setTeachers] = useState(INITIAL_TEACHERS);
            const [students, setStudents] = useState(INITIAL_STUDENTS);
            const [tournaments, setTournaments] = useState(INITIAL_TOURNAMENTS);
            const [activeTournamentId, setActiveTournamentId] = useState('tr_1');
            const [performances, setPerformances] = useState([]);
            const [dbStatus, setDbStatus] = useState('connecting'); // 'connected', 'offline'

            // Sincronización automática con la base de datos MySQL via PHP API
            useEffect(() => {
                fetch('api.php?action=get_all')
                    .then(res => res.json())
                    .then(res => {
                        if (res.success && res.data) {
                            if (res.data.schools?.length) setSchools(res.data.schools);
                            if (res.data.teachers?.length) setTeachers(res.data.teachers);
                            if (res.data.students?.length) setStudents(res.data.students);
                            if (res.data.tournaments?.length) {
                                setTournaments(res.data.tournaments);
                                setActiveTournamentId(res.data.tournaments[0].id);
                            }
                            if (res.data.performances) setPerformances(res.data.performances);
                            setDbStatus('connected');
                        } else {
                            setDbStatus('offline');
                        }
                    })
                    .catch(() => {
                        setDbStatus('offline');
                    });
            }, []);

            const activeTournament = tournaments.find(t => t.id === activeTournamentId) || tournaments[0];

            return (
                <div className="flex flex-col min-h-screen bg-gray-950 text-gray-100 font-sans">
                    {/* Header Navigation */}
                    <header className="bg-gray-900 border-b border-gray-800 sticky top-0 z-50">
                        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                            <div className="flex items-center justify-between h-16">
                                <div className="flex items-center space-x-3 cursor-pointer" onClick={() => setCurrentTab('live')}>
                                    <div className="w-10 h-10 rounded-xl bg-yellow-500 flex items-center justify-center text-gray-950 font-black text-xl shadow-lg shadow-yellow-500/20">
                                        <i className="fa-solid fa-bee text-xl"></i>
                                    </div>
                                    <div>
                                        <h1 className="font-extrabold text-lg sm:text-xl tracking-tight text-white flex items-center gap-2">
                                            SpellingBee <span className="bg-yellow-500/10 text-yellow-400 text-xs px-2 py-0.5 rounded border border-yellow-500/30">PRO</span>
                                        </h1>
                                        <p className="text-xs text-gray-400 hidden sm:block">Sistema de Torneos Educativos</p>
                                    </div>
                                </div>

                                {/* Indicador de Estado de Conexión a MySQL */}
                                <div className="hidden lg:flex items-center gap-2 text-xs font-semibold px-3 py-1 rounded-full border border-gray-800 bg-gray-950">
                                    <span className={`w-2.5 h-2.5 rounded-full ${dbStatus === 'connected' ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'}`}></span>
                                    <span className="text-gray-300">
                                        {dbStatus === 'connected' ? 'MySQL Conectado (XAMPP)' : 'Modo Demo (Sin backend)'}
                                    </span>
                                </div>

                                <nav className="hidden md:flex space-x-1 lg:space-x-2">
                                    <NavButton icon="fa-stopwatch" label="Competencia Live" active={currentTab === 'live'} onClick={() => setCurrentTab('live')} />
                                    <NavButton icon="fa-trophy" label="Torneos" active={currentTab === 'tournaments'} onClick={() => setCurrentTab('tournaments')} />
                                    <NavButton icon="fa-chart-simple" label="Resultados" active={currentTab === 'results'} onClick={() => setCurrentTab('results')} />
                                    <NavButton icon="fa-user-graduate" label="Alumnos" active={currentTab === 'students'} onClick={() => setCurrentTab('students')} />
                                    <NavButton icon="fa-chalkboard-user" label="Profesores" active={currentTab === 'teachers'} onClick={() => setCurrentTab('teachers')} />
                                    <NavButton icon="fa-school" label="Escuelas" active={currentTab === 'schools'} onClick={() => setCurrentTab('schools')} />
                                    <NavButton icon="fa-book-bookmark" label="Palabras" active={currentTab === 'words'} onClick={() => setCurrentTab('words')} />
                                </nav>

                                <div className="md:hidden flex items-center">
                                    <select 
                                        value={currentTab} 
                                        onChange={(e) => setCurrentTab(e.target.value)}
                                        className="bg-gray-800 border border-gray-700 text-yellow-400 text-sm rounded-lg p-2 font-medium"
                                    >
                                        <option value="live">⚡ Competencia Live</option>
                                        <option value="tournaments">🏆 Torneos</option>
                                        <option value="results">📊 Resultados</option>
                                        <option value="students">🎓 Alumnos</option>
                                        <option value="teachers">👨‍🏫 Profesores</option>
                                        <option value="schools">🏫 Escuelas</option>
                                        <option value="words">📖 Banco de Palabras</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </header>

                    {/* Main Content View */}
                    <main className="flex-grow p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
                        {currentTab === 'live' && (
                            <LiveCompetitionView 
                                tournament={activeTournament}
                                students={students}
                                schools={schools}
                                teachers={teachers}
                                performances={performances}
                                setPerformances={setPerformances}
                                setTournaments={setTournaments}
                            />
                        )}

                        {currentTab === 'tournaments' && (
                            <TournamentsView 
                                tournaments={tournaments}
                                setTournaments={setTournaments}
                                activeTournamentId={activeTournamentId}
                                setActiveTournamentId={setActiveTournamentId}
                                students={students}
                                schools={schools}
                                setCurrentTab={setCurrentTab}
                            />
                        )}

                        {currentTab === 'results' && (
                            <ResultsView 
                                tournament={activeTournament}
                                performances={performances}
                                students={students}
                                schools={schools}
                            />
                        )}

                        {currentTab === 'students' && (
                            <StudentsView 
                                students={students}
                                setStudents={setStudents}
                                schools={schools}
                                teachers={teachers}
                            />
                        )}

                        {currentTab === 'teachers' && (
                            <TeachersView 
                                teachers={teachers}
                                setTeachers={setTeachers}
                                schools={schools}
                            />
                        )}

                        {currentTab === 'schools' && (
                            <SchoolsView 
                                schools={schools}
                                setSchools={setSchools}
                            />
                        )}

                        {currentTab === 'words' && (
                            <WordBankView words={WORD_BANK} />
                        )}
                    </main>

                    <footer className="bg-gray-900 border-t border-gray-800 py-4 px-6 text-center text-xs text-gray-500">
                        <div className="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-2">
                            <span>⚡ Sistema Oficial de Torneos Spelling Bee</span>
                            <span>Soporta Controladores ZeroDelay Arcade Encoder (Tecla 'B' para Ocultar/Mostrar Controles)</span>
                        </div>
                    </footer>
                </div>
            );
        }

        function NavButton({ icon, label, active, onClick }) {
            return (
                <button
                    onClick={onClick}
                    className={`flex items-center space-x-2 px-3 py-2 rounded-lg text-sm font-semibold transition-all ${
                        active 
                            ? 'bg-yellow-500 text-gray-950 shadow-md shadow-yellow-500/20' 
                            : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                    }`}
                >
                    <i className={`fa-solid ${icon}`}></i>
                    <span>{label}</span>
                </button>
            );
        }

        function LiveCompetitionView({ tournament, students, schools, teachers, performances, setPerformances, setTournaments }) {
            if (!tournament) {
                return (
                    <div className="text-center py-20 bg-gray-900 rounded-2xl border border-gray-800">
                        <i className="fa-solid fa-trophy text-5xl text-gray-600 mb-4"></i>
                        <h3 className="text-xl font-bold text-gray-300">No hay torneos activos creados</h3>
                    </div>
                );
            }

            const tournamentStudents = students.filter(s => 
                tournament.participantIds.includes(s.id) && s.level === tournament.activeLevel
            );

            const [currentStudentIndex, setCurrentStudentIndex] = useState(0);
            const currentStudent = tournamentStudents[currentStudentIndex] || tournamentStudents[0];
            const currentSchool = currentStudent ? schools.find(s => s.id === currentStudent.schoolId) : null;
            const currentTeacher = currentStudent ? teachers.find(t => t.id === currentStudent.teacherId) : null;

            // Timer & Controls States
            const [timerPhase, setTimerPhase] = useState(1); // 1 = Deletreo, 2 = Oracion
            const [isRunning, setIsRunning] = useState(false);
            const [timeMs, setTimeMs] = useState(0);

            const [savedSpellingTime, setSavedSpellingTime] = useState(null);
            const [savedSentenceTime, setSavedSentenceTime] = useState(null);
            const [penaltiesCount, setPenaltiesCount] = useState(0);

            // Toggle for hiding control buttons (Triggered by 'B' or Arcade Controller button)
            const [showControls, setShowControls] = useState(true);
            const [showKeyHelp, setShowKeyHelp] = useState(false);

            const [toastMessage, setToastMessage] = useState(null);

            const showToast = (msg) => {
                setToastMessage(msg);
                setTimeout(() => setToastMessage(null), 2500);
            };

            const timerRef = useRef(null);

            useEffect(() => {
                if (isRunning) {
                    const startTime = Date.now() - timeMs;
                    timerRef.current = setInterval(() => {
                        setTimeMs(Date.now() - startTime);
                    }, 10);
                } else {
                    clearInterval(timerRef.current);
                }
                return () => clearInterval(timerRef.current);
            }, [isRunning]);

            const resetCurrentPerformance = () => {
                setIsRunning(false);
                setTimeMs(0);
                setTimerPhase(1);
                setSavedSpellingTime(null);
                setSavedSentenceTime(null);
                setPenaltiesCount(0);
            };

            const toggleTimer = () => {
                setIsRunning(prev => !prev);
            };

            const handleSaveCurrentPhaseTime = () => {
                const elapsedSeconds = timeMs / 1000;
                if (timerPhase === 1) {
                    setSavedSpellingTime(elapsedSeconds);
                    setIsRunning(false);
                    setTimeMs(0);
                    setTimerPhase(2);
                    showToast("⏱️ Tiempo de Deletreo guardado. Fase 2: Oración");
                } else if (timerPhase === 2) {
                    setSavedSentenceTime(elapsedSeconds);
                    setIsRunning(false);
                    setTimeMs(0);
                    showToast("⏱️ Tiempo de Oración guardado.");
                }
            };

            const handleAddPenalty = () => setPenaltiesCount(prev => prev + 1);
            const handleRemovePenalty = () => setPenaltiesCount(prev => Math.max(0, prev - 1));

            const handleSaveAndNext = () => {
                if (savedSpellingTime === null) {
                    showToast("⚠️ Guarde primero el tiempo de deletreo.");
                    return;
                }

                const sentenceVal = savedSentenceTime !== null ? savedSentenceTime : 0;
                const penaltyAddedSeconds = penaltiesCount * 5;
                const totalCalculated = savedSpellingTime + sentenceVal + penaltyAddedSeconds;

                const newPerformanceRecord = {
                    id: 'perf_' + Date.now(),
                    tournamentId: tournament.id,
                    studentId: currentStudent.id,
                    level: tournament.activeLevel,
                    round: tournament.activeRound,
                    spellingTimeSec: savedSpellingTime,
                    sentenceTimeSec: sentenceVal,
                    penalties: penaltiesCount,
                    totalTimeSec: totalCalculated,
                    timestamp: new Date().toISOString()
                };

                // Actualizar estado local inmediatamente para la UI
                setPerformances(prev => [newPerformanceRecord, ...prev]);

                // Guardar persistentemente en la Base de Datos MySQL via api.php
                fetch('api.php?action=save_performance', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(newPerformanceRecord)
                })
                .then(res => res.json())
                .then(resData => {
                    if (resData.success) {
                        showToast(`💾 Guardado en MySQL DB (${currentStudent.name})`);
                    } else {
                        showToast(`✅ Guardado en memoria (${currentStudent.name})`);
                    }
                })
                .catch(() => {
                    showToast(`✅ Guardado en memoria (${currentStudent.name})`);
                });

                if (currentStudentIndex < tournamentStudents.length - 1) {
                    setCurrentStudentIndex(prev => prev + 1);
                    resetCurrentPerformance();
                } else {
                    showToast(`🏆 Final de la lista para Level ${tournament.activeLevel} - Round ${tournament.activeRound}`);
                    resetCurrentPerformance();
                }
            };

            // Keyboard & ZeroDelay USB Arcade Encoder Listener
            useEffect(() => {
                const handleKeyDown = (e) => {
                    // Ignore keypresses if user is typing in an input
                    if (['INPUT', 'SELECT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;

                    const key = e.key.toLowerCase();

                    // 'B' key toggles operational buttons on/off
                    if (key === 'b') {
                        e.preventDefault();
                        setShowControls(prev => !prev);
                        showToast(showControls ? "👁️ MODO PROYECCIÓN: Botones ocultados" : "🕹️ MODO CONTROL: Botones visibles");
                    }

                    // Space or 'S': Start/Stop timer
                    if (e.code === 'Space' || key === 's') {
                        e.preventDefault();
                        toggleTimer();
                    }

                    // Enter or 'G': Save current phase time
                    if (e.code === 'Enter' || key === 'g') {
                        e.preventDefault();
                        handleSaveCurrentPhaseTime();
                    }

                    // '+' or 'P' or ArrowUp: Add penalty
                    if (key === '+' || key === 'p' || e.code === 'ArrowUp') {
                        e.preventDefault();
                        handleAddPenalty();
                    }

                    // '-' or 'M' or ArrowDown: Remove penalty
                    if (key === '-' || key === 'm' || e.code === 'ArrowDown') {
                        e.preventDefault();
                        handleRemovePenalty();
                    }

                    // 'R': Reset timer
                    if (key === 'r') {
                        e.preventDefault();
                        resetCurrentPerformance();
                    }

                    // ArrowRight or 'N': Next student
                    if (e.code === 'ArrowRight' || key === 'n') {
                        if (currentStudentIndex < tournamentStudents.length - 1) {
                            setCurrentStudentIndex(prev => prev + 1);
                            resetCurrentPerformance();
                        }
                    }

                    // ArrowLeft: Previous student
                    if (e.code === 'ArrowLeft') {
                        if (currentStudentIndex > 0) {
                            setCurrentStudentIndex(prev => prev - 1);
                            resetCurrentPerformance();
                        }
                    }
                };

                window.addEventListener('keydown', handleKeyDown);
                return () => window.removeEventListener('keydown', handleKeyDown);
            }, [showControls, isRunning, timeMs, timerPhase, currentStudentIndex, tournamentStudents]);

            const formatTimerDisplay = (ms) => {
                const totalSec = ms / 1000;
                const mins = Math.floor(totalSec / 60);
                const secs = (totalSec % 60).toFixed(2);
                return `${mins.toString().padStart(2, '0')}:${secs.padStart(5, '0')}`;
            };

            const formatSec = (sec) => sec !== null ? `${sec.toFixed(2)}s` : '00:00:00';

            const totalCalculatedTimeWithPenalties = 
                (savedSpellingTime || 0) + 
                (savedSentenceTime || 0) + 
                (penaltiesCount * 5);

            if (!currentStudent) {
                return (
                    <div className="text-center py-16 bg-gray-900 rounded-3xl border border-gray-800 p-8">
                        <i className="fa-solid fa-users-slash text-5xl text-yellow-500 mb-4"></i>
                        <h3 className="text-2xl font-bold">No hay participantes para el Level {tournament.activeLevel}</h3>
                        <div className="mt-6">
                            <button 
                                onClick={() => {
                                    setTournaments(prev => prev.map(t => t.id === tournament.id ? { ...t, activeLevel: t.activeLevel % 3 + 1 } : t));
                                }}
                                className="bg-yellow-500 text-black px-5 py-2.5 rounded-xl font-bold hover:bg-yellow-400"
                            >
                                Cambiar a Level {tournament.activeLevel % 3 + 1}
                            </button>
                        </div>
                    </div>
                );
            }

            return (
                <div className="space-y-5">
                    {/* Toast Notification */}
                    {toastMessage && (
                        <div className="fixed bottom-6 right-6 bg-yellow-500 text-gray-950 font-black px-6 py-3 rounded-2xl shadow-2xl z-50 animate-bounce flex items-center gap-3 border-2 border-yellow-300">
                            <i className="fa-solid fa-gamepad text-xl"></i>
                            <span>{toastMessage}</span>
                        </div>
                    )}

                    {/* TOP HEADER: CONCURSO TITLE (Matches sketch top "CONCURSO") */}
                    <div className="bg-gradient-to-r from-gray-900 via-gray-950 to-yellow-950/50 p-4 sm:p-5 rounded-2xl border border-yellow-500/30 flex justify-between items-center shadow-2xl">
                        <div className="flex items-center gap-3">
                            <div className="w-8 h-8 rounded-lg bg-yellow-500 flex items-center justify-center text-gray-950 font-black">
                                <i className="fa-solid fa-crown text-sm"></i>
                            </div>
                            <h1 className="text-2xl sm:text-4xl font-black text-white tracking-wider uppercase font-mono">
                                CONCURSO: <span className="text-yellow-400">{tournament.name}</span>
                            </h1>
                        </div>

                        {/* Top quick controls */}
                        <div className="flex items-center gap-2">
                            <button 
                                onClick={() => setShowControls(prev => !prev)}
                                className={`px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 border ${
                                    showControls ? 'bg-gray-800 text-yellow-400 border-yellow-500/30' : 'bg-yellow-500 text-gray-950 border-yellow-400'
                                }`}
                                title="Presione 'B' para Ocultar/Mostrar"
                            >
                                <i className={`fa-solid ${showControls ? 'fa-eye-slash' : 'fa-eye'}`}></i>
                                <span>{showControls ? "Modo Proyección (B)" : "Mostrar Controles (B)"}</span>
                            </button>

                            <button 
                                onClick={() => setShowKeyHelp(true)}
                                className="bg-gray-800 hover:bg-gray-700 text-gray-300 px-3 py-1.5 rounded-xl text-xs font-bold border border-gray-700"
                            >
                                <i className="fa-solid fa-keyboard mr-1"></i> Teclado ZeroDelay
                            </button>
                        </div>
                    </div>

                    {/* BANNER 2: INSTITUCION [X] - ALUMNO - PROFESOR - AÑO (Matches sketch layout) */}
                    <div className="bg-gray-900 border-2 border-gray-800 rounded-2xl p-4 shadow-lg">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
                            
                            {/* INSTITUCION [X] */}
                            <div className="flex items-center gap-3 border-r-0 md:border-r border-gray-800 pr-2">
                                <div className="w-14 h-14 rounded-xl bg-gray-950 border-2 border-yellow-500/40 flex items-center justify-center text-yellow-400 font-black text-2xl shrink-0 shadow-inner">
                                    <i className="fa-solid fa-school"></i>
                                </div>
                                <div className="overflow-hidden">
                                    <span className="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Institución</span>
                                    <h3 className="text-base font-extrabold text-white truncate">{currentSchool ? currentSchool.name : 'Institución'}</h3>
                                    <p className="text-xs text-gray-400">{currentSchool?.city || '-'}</p>
                                </div>
                            </div>

                            {/* ALUMNO */}
                            <div className="border-r-0 md:border-r border-gray-800 px-0 md:px-2">
                                <span className="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Alumno</span>
                                <h3 className="text-xl font-black text-yellow-400 truncate">{currentStudent.name}</h3>
                                <p className="text-xs text-gray-400 font-mono">DNI: {currentStudent.dni}</p>
                            </div>

                            {/* PROFESOR */}
                            <div className="border-r-0 md:border-r border-gray-800 px-0 md:px-2">
                                <span className="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Profesor</span>
                                <h3 className="text-base font-bold text-gray-200 truncate">{currentTeacher ? currentTeacher.name : 'Profesor A Cargo'}</h3>
                                <p className="text-xs text-gray-400">{currentTeacher ? `DNI: ${currentTeacher.dni}` : '-'}</p>
                            </div>

                            {/* AÑO */}
                            <div className="pl-0 md:pl-2">
                                <span className="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Año</span>
                                <h3 className="text-2xl font-black text-white">{currentStudent.year}mo Año</h3>
                                <p className="text-xs text-yellow-500/80 font-bold">División "{currentStudent.division}"</p>
                            </div>

                        </div>
                    </div>

                    {/* MAIN CARDS ROW (Exact arrangement from sketch: Level | Tiempo | Round | Penalty) */}
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
                        
                        {/* 1. LEVEL CARD */}
                        <div className="lg:col-span-2 bg-gray-900 border-2 border-yellow-500/30 rounded-3xl p-5 flex flex-col items-center justify-center text-center shadow-2xl">
                            <span className="text-xs font-black uppercase text-gray-400 tracking-widest mb-1">
                                LEVEL
                            </span>
                            <div className="text-6xl xl:text-7xl font-black text-yellow-400 bg-yellow-500/10 border-2 border-yellow-500/30 w-full py-4 rounded-2xl glow-bee font-mono">
                                {tournament.activeLevel}
                            </div>
                        </div>

                        {/* 2. TIEMPO / CRONOMETRO CARD */}
                        <div className="lg:col-span-6 bg-gray-950 border-2 border-yellow-500/40 rounded-3xl p-6 flex flex-col items-center justify-center text-center shadow-2xl relative overflow-hidden">
                            <span className="text-xs font-black uppercase tracking-widest text-yellow-400 mb-1 flex items-center gap-2">
                                <i className="fa-solid fa-stopwatch animate-pulse"></i> TIEMPO ({timerPhase === 1 ? 'DELETREO' : 'ORACIÓN'})
                            </span>
                            
                            {/* Big Digital Clock Display */}
                            <div className="text-6xl sm:text-7xl xl:text-8xl font-black text-white font-mono-timer tracking-tight my-2 drop-shadow-lg">
                                {formatTimerDisplay(timeMs)}
                            </div>

                            {/* Control Buttons (Hidden when 'B' is pressed for Projection Mode) */}
                            {showControls && (
                                <div className="flex flex-wrap items-center justify-center gap-2 mt-4 w-full border-t border-gray-800 pt-4">
                                    <button
                                        onClick={toggleTimer}
                                        className={`px-5 py-2.5 rounded-xl font-black text-sm shadow-lg flex items-center gap-2 transition-all ${
                                            isRunning 
                                                ? 'bg-red-600 hover:bg-red-500 text-white animate-pulse' 
                                                : 'bg-emerald-500 hover:bg-emerald-400 text-gray-950'
                                        }`}
                                    >
                                        <i className={`fa-solid ${isRunning ? 'fa-pause' : 'fa-play'}`}></i>
                                        <span>{isRunning ? 'PARAR (Space)' : 'START / STOP (Space)'}</span>
                                    </button>

                                    <button
                                        onClick={handleSaveCurrentPhaseTime}
                                        disabled={timeMs === 0}
                                        className="bg-yellow-500 hover:bg-yellow-400 disabled:opacity-30 text-gray-950 px-5 py-2.5 rounded-xl font-black text-sm shadow-lg flex items-center gap-2"
                                    >
                                        <i className="fa-solid fa-floppy-disk"></i>
                                        <span>Guardar Tiempo (Enter)</span>
                                    </button>

                                    <button
                                        onClick={resetCurrentPerformance}
                                        className="bg-gray-800 hover:bg-gray-700 text-gray-300 px-3 py-2.5 rounded-xl font-bold text-xs"
                                        title="Reiniciar Cronómetro (R)"
                                    >
                                        <i className="fa-solid fa-rotate-left"></i>
                                    </button>
                                </div>
                            )}
                        </div>

                        {/* 3. ROUND CARD */}
                        <div className="lg:col-span-2 bg-gray-900 border-2 border-gray-800 rounded-3xl p-5 flex flex-col items-center justify-center text-center shadow-2xl">
                            <span className="text-xs font-black uppercase text-gray-400 tracking-widest mb-1">
                                ROUND
                            </span>
                            <div className="text-6xl xl:text-7xl font-black text-white bg-gray-950 border-2 border-gray-800 w-full py-4 rounded-2xl font-mono">
                                {tournament.activeRound}
                            </div>
                        </div>

                        {/* 4. PENALTY CARD */}
                        <div className="lg:col-span-2 bg-gray-900 border-2 border-red-500/30 rounded-3xl p-5 flex flex-col items-center justify-center text-center shadow-2xl">
                            <span className="text-xs font-black uppercase text-red-400 tracking-widest mb-1 flex items-center gap-1">
                                <i className="fa-solid fa-triangle-exclamation"></i> PENALTY
                            </span>
                            <div className="text-6xl xl:text-7xl font-black text-red-500 bg-red-500/10 border-2 border-red-500/30 w-full py-4 rounded-2xl glow-red font-mono">
                                {penaltiesCount}
                            </div>

                            {/* Penalty Adjustment Buttons (+ / -) */}
                            {showControls && (
                                <div className="grid grid-cols-2 gap-2 w-full mt-3">
                                    <button
                                        onClick={handleRemovePenalty}
                                        className="bg-gray-800 hover:bg-gray-700 text-white font-extrabold text-lg py-1.5 rounded-xl border border-gray-700"
                                        title="Restar Penalización (-)"
                                    >
                                        -
                                    </button>
                                    <button
                                        onClick={handleAddPenalty}
                                        className="bg-red-600 hover:bg-red-500 text-white font-extrabold text-lg py-1.5 rounded-xl border border-red-500 shadow-md shadow-red-600/30"
                                        title="Sumar Penalización (+)"
                                    >
                                        +
                                    </button>
                                </div>
                            )}
                        </div>

                    </div>

                    {/* BOTTOM READOUT BAR (Matches sketch bottom row: Deletreo | Oracion | Penalty | Total) */}
                    <div className="bg-gray-900 border-2 border-gray-800 rounded-3xl p-5 shadow-2xl">
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-center">
                            
                            {/* Deletreo */}
                            <div className="bg-gray-950 p-4 rounded-2xl border border-gray-800">
                                <span className="text-xs font-bold uppercase text-gray-400 block mb-1">Deletreo</span>
                                <span className="text-3xl font-black text-yellow-400 font-mono-timer">
                                    {formatSec(savedSpellingTime)}
                                </span>
                            </div>

                            {/* Oracion */}
                            <div className="bg-gray-950 p-4 rounded-2xl border border-gray-800">
                                <span className="text-xs font-bold uppercase text-gray-400 block mb-1">Oración</span>
                                <span className="text-3xl font-black text-yellow-400 font-mono-timer">
                                    {formatSec(savedSentenceTime)}
                                </span>
                            </div>

                            {/* Penalty */}
                            <div className="bg-gray-950 p-4 rounded-2xl border border-gray-800">
                                <span className="text-xs font-bold uppercase text-red-400 block mb-1">Penalty</span>
                                <span className="text-3xl font-black text-red-500 font-mono-timer">
                                    +{penaltiesCount * 5}.00s
                                </span>
                            </div>

                            {/* Total */}
                            <div className="bg-gradient-to-r from-yellow-500 to-amber-500 p-4 rounded-2xl text-gray-950 shadow-lg shadow-yellow-500/20">
                                <span className="text-xs font-black uppercase tracking-wider block opacity-90">TOTAL</span>
                                <span className="text-4xl font-black font-mono-timer">
                                    {totalCalculatedTimeWithPenalties.toFixed(2)}s
                                </span>
                            </div>

                        </div>

                        {/* Complete & Next Student Control */}
                        {showControls && (
                            <div className="mt-5 pt-4 border-t border-gray-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                                <div className="flex items-center gap-2">
                                    <button
                                        disabled={currentStudentIndex === 0}
                                        onClick={() => {
                                            setCurrentStudentIndex(prev => prev - 1);
                                            resetCurrentPerformance();
                                        }}
                                        className="bg-gray-800 hover:bg-gray-700 disabled:opacity-30 text-xs font-bold text-gray-300 px-4 py-2 rounded-xl"
                                    >
                                        <i className="fa-solid fa-chevron-left mr-1"></i> Participante Anterior (←)
                                    </button>
                                    <span className="text-xs font-bold text-gray-400">
                                        {currentStudentIndex + 1} / {tournamentStudents.length}
                                    </span>
                                </div>

                                <button
                                    onClick={handleSaveAndNext}
                                    className="w-full sm:w-auto bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-400 hover:to-green-500 text-gray-950 font-black text-base px-8 py-3 rounded-2xl shadow-xl shadow-emerald-500/20 flex items-center justify-center gap-2 transition-all transform active:scale-95"
                                >
                                    <i className="fa-solid fa-check-circle text-lg"></i>
                                    <span>GUARDAR Y SIGUIENTE PARTICIPANTE (→)</span>
                                </button>
                            </div>
                        )}
                    </div>

                    {/* MODAL HELPER: ZeroDelay Arcade Keyboard Mappings */}
                    {showKeyHelp && (
                        <div className="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                            <div className="bg-gray-900 border border-gray-800 rounded-3xl max-w-md w-full p-6 shadow-2xl">
                                <div className="flex justify-between items-center mb-4">
                                    <h3 className="text-xl font-bold text-white flex items-center gap-2">
                                        <i className="fa-solid fa-gamepad text-yellow-400"></i>
                                        <span>Mapeo ZeroDelay / Teclado</span>
                                    </h3>
                                    <button onClick={() => setShowKeyHelp(false)} className="text-gray-400 hover:text-white">
                                        <i className="fa-solid fa-xmark text-xl"></i>
                                    </button>
                                </div>

                                <div className="space-y-3 text-sm">
                                    <div className="flex justify-between p-2 bg-gray-950 rounded-xl">
                                        <span className="text-gray-400">Ocultar/Mostrar Controles:</span>
                                        <span className="font-mono font-bold text-yellow-400">Tecla B</span>
                                    </div>
                                    <div className="flex justify-between p-2 bg-gray-950 rounded-xl">
                                        <span className="text-gray-400">Start / Stop Cronómetro:</span>
                                        <span className="font-mono font-bold text-yellow-400">Espacio / Tecla S</span>
                                    </div>
                                    <div className="flex justify-between p-2 bg-gray-950 rounded-xl">
                                        <span className="text-gray-400">Guardar Parcial (Deletreo/Oración):</span>
                                        <span className="font-mono font-bold text-yellow-400">Enter / Tecla G</span>
                                    </div>
                                    <div className="flex justify-between p-2 bg-gray-950 rounded-xl">
                                        <span className="text-gray-400">Sumar Penalización (+5s):</span>
                                        <span className="font-mono font-bold text-yellow-400">+ / Tecla P / Flecha Arriba</span>
                                    </div>
                                    <div className="flex justify-between p-2 bg-gray-950 rounded-xl">
                                        <span className="text-gray-400">Restar Penalización (-5s):</span>
                                        <span className="font-mono font-bold text-yellow-400">- / Tecla M / Flecha Abajo</span>
                                    </div>
                                    <div className="flex justify-between p-2 bg-gray-950 rounded-xl">
                                        <span className="text-gray-400">Siguiente / Anterior Alumno:</span>
                                        <span className="font-mono font-bold text-yellow-400">Flecha Der / Izq</span>
                                    </div>
                                </div>

                                <div className="mt-6 text-center">
                                    <button
                                        onClick={() => setShowKeyHelp(false)}
                                        className="bg-yellow-500 text-gray-950 font-bold px-6 py-2 rounded-xl text-sm"
                                    >
                                        Entendido
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            );
        }

        function TournamentsView({ tournaments, setTournaments, activeTournamentId, setActiveTournamentId, students, schools, setCurrentTab }) {
            const [showCreateModal, setShowCreateModal] = useState(false);
            const [newTournament, setNewTournament] = useState({
                name: '',
                type: 'inter_school', // classroom, inter_class, inter_school
                roundsCount: 3,
                maxAdvancingPerRound: 3,
                activeLevel: 1,
                activeRound: 1,
                participantIds: students.map(s => s.id)
            });

            const handleCreateTournament = (e) => {
                e.preventDefault();
                if (!newTournament.name) return;

                const created = {
                    ...newTournament,
                    id: 'tr_' + Date.now(),
                    status: 'active'
                };

                setTournaments(prev => [created, ...prev]);
                setActiveTournamentId(created.id);
                setShowCreateModal(false);
            };

            return (
                <div className="space-y-6">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h2 className="text-2xl font-black text-white">Gestión de Torneos y Competencias</h2>
                            <p className="text-gray-400 text-sm">Configure torneos por curso, intercurso o interinstitucionales</p>
                        </div>
                        <button 
                            onClick={() => setShowCreateModal(true)}
                            className="bg-yellow-500 hover:bg-yellow-400 text-gray-950 font-extrabold px-5 py-2.5 rounded-xl flex items-center gap-2 shadow-lg shadow-yellow-500/20"
                        >
                            <i className="fa-solid fa-plus"></i>
                            <span>Nuevo Torneo</span>
                        </button>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        {tournaments.map(tr => {
                            const isSelected = tr.id === activeTournamentId;
                            return (
                                <div 
                                    key={tr.id}
                                    className={`bg-gray-900 border rounded-2xl p-6 flex flex-col justify-between transition-all ${
                                        isSelected ? 'border-yellow-500 shadow-xl shadow-yellow-500/10' : 'border-gray-800 hover:border-gray-700'
                                    }`}
                                >
                                    <div>
                                        <div className="flex justify-between items-start gap-2 mb-3">
                                            <span className="text-[10px] uppercase font-extrabold bg-gray-800 text-yellow-400 px-2.5 py-1 rounded-md border border-gray-700">
                                                {tr.type === 'classroom' ? 'Abono Curso' : tr.type === 'inter_class' ? 'Intercurso' : 'Interinstitucional'}
                                            </span>
                                            {isSelected && (
                                                <span className="text-xs font-bold bg-yellow-500 text-gray-950 px-2 py-0.5 rounded-full">
                                                    ACTIVO EN LIVE
                                                </span>
                                            )}
                                        </div>

                                        <h3 className="text-lg font-extrabold text-white mb-2">{tr.name}</h3>

                                        <div className="space-y-2 text-xs text-gray-400 mt-4 border-t border-gray-800 pt-3">
                                            <div className="flex justify-between">
                                                <span>Cantidad de Rounds:</span>
                                                <span className="font-bold text-gray-200">{tr.roundsCount} Rounds</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Pasan por Round:</span>
                                                <span className="font-bold text-gray-200">{tr.maxAdvancingPerRound} Participantes</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Total Inscriptos:</span>
                                                <span className="font-bold text-gray-200">{tr.participantIds.length} Alumnos</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="mt-6 pt-4 border-t border-gray-800 flex gap-2">
                                        <button
                                            onClick={() => {
                                                setActiveTournamentId(tr.id);
                                                setCurrentTab('live');
                                            }}
                                            className="w-full bg-gray-800 hover:bg-yellow-500 hover:text-gray-950 text-white font-bold text-sm py-2 rounded-xl transition-all flex items-center justify-center gap-2"
                                        >
                                            <i className="fa-solid fa-bolt"></i>
                                            <span>Lanzar Competencia</span>
                                        </button>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {/* MODAL NUEVO TORNEO */}
                    {showCreateModal && (
                        <div className="fixed inset-0 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4 z-50">
                            <div className="bg-gray-900 border border-gray-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl">
                                <h3 className="text-xl font-bold text-white mb-4">Configurar Nuevo Torneo</h3>
                                <form onSubmit={handleCreateTournament} className="space-y-4">
                                    <div>
                                        <label className="text-xs font-bold text-gray-300 block mb-1">Nombre del Torneo</label>
                                        <input 
                                            type="text" 
                                            required
                                            placeholder="Ej: Spelling Bee Interescolar 2026"
                                            value={newTournament.name}
                                            onChange={(e) => setNewTournament({...newTournament, name: e.target.value})}
                                            className="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-yellow-500 outline-none"
                                        />
                                    </div>

                                    <div>
                                        <label className="text-xs font-bold text-gray-300 block mb-1">Tipo de Competencia</label>
                                        <select 
                                            value={newTournament.type}
                                            onChange={(e) => setNewTournament({...newTournament, type: e.target.value})}
                                            className="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-yellow-500 outline-none"
                                        >
                                            <option value="classroom">Interno de un Curso / División</option>
                                            <option value="inter_class">Intercurso de una Institución</option>
                                            <option value="inter_school">Competencia Interinstitucional</option>
                                        </select>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="text-xs font-bold text-gray-300 block mb-1">Cantidad de Rounds</label>
                                            <input 
                                                type="number" 
                                                min="1" 
                                                max="10"
                                                value={newTournament.roundsCount}
                                                onChange={(e) => setNewTournament({...newTournament, roundsCount: parseInt(e.target.value)})}
                                                className="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-yellow-500 outline-none"
                                            />
                                        </div>
                                        <div>
                                            <label className="text-xs font-bold text-gray-300 block mb-1">Pasan por Round</label>
                                            <input 
                                                type="number" 
                                                min="1"
                                                value={newTournament.maxAdvancingPerRound}
                                                onChange={(e) => setNewTournament({...newTournament, maxAdvancingPerRound: parseInt(e.target.value)})}
                                                className="w-full bg-gray-950 border border-gray-800 rounded-xl px-4 py-2.5 text-white text-sm focus:border-yellow-500 outline-none"
                                            />
                                        </div>
                                    </div>

                                    <div className="flex justify-end gap-3 pt-4 border-t border-gray-800">
                                        <button 
                                            type="button"
                                            onClick={() => setShowCreateModal(false)}
                                            className="px-4 py-2 text-sm font-bold text-gray-400 hover:text-white"
                                        >
                                            Cancelar
                                        </button>
                                        <button 
                                            type="submit"
                                            className="bg-yellow-500 hover:bg-yellow-400 text-gray-950 font-bold px-5 py-2 rounded-xl text-sm"
                                        >
                                            Crear Torneo
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}
                </div>
            );
        }

        function ResultsView({ tournament, performances, students, schools }) {
            const [selectedLevel, setSelectedLevel] = useState(1);
            const [selectedRound, setSelectedRound] = useState(1);

            if (!tournament) return null;

            // Filter performances for tournament, level, round
            const filteredPerformances = performances.filter(p => 
                p.tournamentId === tournament.id && p.level === selectedLevel && p.round === selectedRound
            );

            // Sort by total calculated time ascending (lowest time = best rank)
            const sorted = [...filteredPerformances].sort((a, b) => a.totalTimeSec - b.totalTimeSec);

            return (
                <div className="space-y-6">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <h2 className="text-2xl font-black text-white">Tabla de Posiciones y Tiempos</h2>
                            <p className="text-gray-400 text-sm">Resumen oficial de tiempos por deletreo, oración y penalizaciones</p>
                        </div>

                        {/* Filter selectors */}
                        <div className="flex items-center gap-3">
                            <select 
                                value={selectedLevel}
                                onChange={(e) => setSelectedLevel(parseInt(e.target.value))}
                                className="bg-gray-900 border border-gray-800 text-yellow-400 font-bold text-sm rounded-xl px-3 py-2"
                            >
                                <option value={1}>Level 1</option>
                                <option value={2}>Level 2</option>
                                <option value={3}>Level 3</option>
                            </select>

                            <select 
                                value={selectedRound}
                                onChange={(e) => setSelectedRound(parseInt(e.target.value))}
                                className="bg-gray-900 border border-gray-800 text-yellow-400 font-bold text-sm rounded-xl px-3 py-2"
                            >
                                {[...Array(tournament.roundsCount)].map((_, i) => (
                                    <option key={i+1} value={i+1}>Round {i+1}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {/* Table */}
                    <div className="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden shadow-xl">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm text-gray-300">
                                <thead className="bg-gray-950 text-gray-400 uppercase text-[11px] tracking-wider border-b border-gray-800">
                                    <tr>
                                        <th className="py-4 px-6">Posición</th>
                                        <th className="py-4 px-6">Alumno</th>
                                        <th className="py-4 px-6">Institución</th>
                                        <th className="py-4 px-6 text-center">Deletreo</th>
                                        <th className="py-4 px-6 text-center">Oración</th>
                                        <th className="py-4 px-6 text-center">Penalizaciones</th>
                                        <th className="py-4 px-6 text-right">Tiempo Total</th>
                                        <th className="py-4 px-6 text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-800">
                                    {sorted.length === 0 ? (
                                        <tr>
                                            <td colSpan="8" className="py-12 text-center text-gray-500">
                                                No hay performances registradas para el Nivel {selectedLevel} - Round {selectedRound}.
                                            </td>
                                        </tr>
                                    ) : (
                                        sorted.map((p, idx) => {
                                            const student = students.find(s => s.id === p.studentId);
                                            const school = student ? schools.find(s => s.id === student.schoolId) : null;
                                            const isQualified = idx < tournament.maxAdvancingPerRound;

                                            return (
                                                <tr key={p.id} className="hover:bg-gray-800/50 transition-colors">
                                                    <td className="py-4 px-6 font-extrabold text-base">
                                                        {idx === 0 ? '🥇 1º' : idx === 1 ? '🥈 2º' : idx === 2 ? '🥉 3º' : `${idx + 1}º`}
                                                    </td>
                                                    <td className="py-4 px-6 font-bold text-white">
                                                        {student ? student.name : 'Desconocido'}
                                                    </td>
                                                    <td className="py-4 px-6 text-gray-400">
                                                        {school ? school.name : '-'}
                                                    </td>
                                                    <td className="py-4 px-6 text-center font-mono-timer text-yellow-400">
                                                        {p.spellingTimeSec.toFixed(2)}s
                                                    </td>
                                                    <td className="py-4 px-6 text-center font-mono-timer text-yellow-400">
                                                        {p.sentenceTimeSec.toFixed(2)}s
                                                    </td>
                                                    <td className="py-4 px-6 text-center font-bold text-red-400">
                                                        {p.penalties} (+{p.penalties * 5}s)
                                                    </td>
                                                    <td className="py-4 px-6 text-right font-black text-lg text-white font-mono-timer">
                                                        {p.totalTimeSec.toFixed(2)}s
                                                    </td>
                                                    <td className="py-4 px-6 text-center">
                                                        <span className={`px-2.5 py-1 rounded-full text-xs font-extrabold ${
                                                            isQualified 
                                                                ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' 
                                                                : 'bg-gray-800 text-gray-500'
                                                        }`}>
                                                            {isQualified ? 'CLASIFICADO' : 'PARTICIPÓ'}
                                                        </span>
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            );
        }

        function StudentsView({ students, setStudents, schools, teachers }) {
            const [showModal, setShowModal] = useState(false);
            const [editingStudent, setEditingStudent] = useState(null);
            const [formData, setFormData] = useState({
                dni: '', name: '', year: 1, schoolId: schools[0]?.id || '', teacherId: teachers[0]?.id || '', division: 'A'
            });

            const handleSubmit = (e) => {
                e.preventDefault();
                const levelInfo = getLevelForYear(formData.year);

                if (editingStudent) {
                    setStudents(prev => prev.map(s => s.id === editingStudent.id ? { ...formData, level: levelInfo.level, id: s.id } : s));
                } else {
                    setStudents(prev => [...prev, { ...formData, level: levelInfo.level, id: 'std_' + Date.now() }]);
                }
                setShowModal(false);
            };

            return (
                <div className="space-y-6">
                    <div className="flex justify-between items-center">
                        <div>
                            <h2 className="text-2xl font-black text-white">Alumnos Participantes</h2>
                            <p className="text-gray-400 text-sm">Los niveles se auto-asignan: 1º-3º (Level 1), 4º-5º (Level 2), 6º-7º (Level 3)</p>
                        </div>
                        <button 
                            onClick={() => {
                                setEditingStudent(null);
                                setFormData({ dni: '', name: '', year: 1, schoolId: schools[0]?.id || '', teacherId: teachers[0]?.id || '', division: 'A' });
                                setShowModal(true);
                            }}
                            className="bg-yellow-500 hover:bg-yellow-400 text-gray-950 font-bold px-4 py-2 rounded-xl text-sm"
                        >
                            <i className="fa-solid fa-user-plus mr-1"></i> Cargar Alumno
                        </button>
                    </div>

                    <div className="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
                        <table className="w-full text-left text-sm text-gray-300">
                            <thead className="bg-gray-950 text-gray-400 uppercase text-[11px] border-b border-gray-800">
                                <tr>
                                    <th className="py-3 px-4">DNI</th>
                                    <th className="py-3 px-4">Alumno</th>
                                    <th className="py-3 px-4">Año / Curso</th>
                                    <th className="py-3 px-4">Nivel Asignado</th>
                                    <th className="py-3 px-4">Institución</th>
                                    <th className="py-3 px-4">Profesor Asesor</th>
                                    <th className="py-3 px-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-800">
                                {students.map(s => {
                                    const sch = schools.find(sc => sc.id === s.schoolId);
                                    const tch = teachers.find(t => t.id === s.teacherId);
                                    return (
                                        <tr key={s.id} className="hover:bg-gray-800/40">
                                            <td className="py-3 px-4 font-mono font-bold text-gray-200">{s.dni}</td>
                                            <td className="py-3 px-4 font-bold text-white">{s.name}</td>
                                            <td className="py-3 px-4">{s.year}º Año ("{s.division}")</td>
                                            <td className="py-3 px-4">
                                                <span className="bg-yellow-500/10 text-yellow-400 text-xs font-bold px-2 py-0.5 rounded border border-yellow-500/20">
                                                    Level {s.level}
                                                </span>
                                            </td>
                                            <td className="py-3 px-4 text-gray-400">{sch ? sch.name : '-'}</td>
                                            <td className="py-3 px-4 text-gray-400">{tch ? tch.name : '-'}</td>
                                            <td className="py-3 px-4 text-right space-x-2">
                                                <button 
                                                    onClick={() => {
                                                        setEditingStudent(s);
                                                        setFormData(s);
                                                        setShowModal(true);
                                                    }}
                                                    className="text-gray-400 hover:text-white"
                                                >
                                                    <i className="fa-solid fa-pen-to-square"></i>
                                                </button>
                                                <button 
                                                    onClick={() => setStudents(prev => prev.filter(x => x.id !== s.id))}
                                                    className="text-red-400 hover:text-red-300"
                                                >
                                                    <i className="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    {showModal && (
                        <div className="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
                            <div className="bg-gray-900 border border-gray-800 rounded-2xl max-w-md w-full p-6">
                                <h3 className="text-xl font-bold text-white mb-4">{editingStudent ? 'Editar Alumno' : 'Nuevo Alumno'}</h3>
                                <form onSubmit={handleSubmit} className="space-y-4">
                                    <div>
                                        <label className="text-xs font-bold text-gray-300 block mb-1">DNI del Alumno</label>
                                        <input type="text" required value={formData.dni} onChange={e=>setFormData({...formData, dni: e.target.value})} className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white" />
                                    </div>
                                    <div>
                                        <label className="text-xs font-bold text-gray-300 block mb-1">Nombre Completo</label>
                                        <input type="text" required value={formData.name} onChange={e=>setFormData({...formData, name: e.target.value})} className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white" />
                                    </div>
                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <label className="text-xs font-bold text-gray-300 block mb-1">Año (1º a 7º)</label>
                                            <input type="number" min="1" max="7" required value={formData.year} onChange={e=>setFormData({...formData, year: parseInt(e.target.value)})} className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white" />
                                        </div>
                                        <div>
                                            <label className="text-xs font-bold text-gray-300 block mb-1">División</label>
                                            <input type="text" required value={formData.division} onChange={e=>setFormData({...formData, division: e.target.value})} className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white" />
                                        </div>
                                    </div>
                                    <div>
                                        <label className="text-xs font-bold text-gray-300 block mb-1">Institución</label>
                                        <select value={formData.schoolId} onChange={e=>setFormData({...formData, schoolId: e.target.value})} className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white">
                                            {schools.map(sc => <option key={sc.id} value={sc.id}>{sc.name}</option>)}
                                        </select>
                                    </div>
                                    <div>
                                        <label className="text-xs font-bold text-gray-300 block mb-1">Profesor Asesor</label>
                                        <select value={formData.teacherId} onChange={e=>setFormData({...formData, teacherId: e.target.value})} className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white">
                                            {teachers.map(tc => <option key={tc.id} value={tc.id}>{tc.name}</option>)}
                                        </select>
                                    </div>
                                    <div className="flex justify-end gap-2 pt-3">
                                        <button type="button" onClick={()=>setShowModal(false)} className="px-4 py-2 text-sm text-gray-400">Cancelar</button>
                                        <button type="submit" className="bg-yellow-500 text-gray-950 font-bold px-4 py-2 rounded-xl text-sm">Guardar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}
                </div>
            );
        }

        function TeachersView({ teachers, setTeachers, schools }) {
            const [showModal, setShowModal] = useState(false);
            const [formData, setFormData] = useState({ dni: '', name: '', schoolId: schools[0]?.id || '', email: '' });

            const handleSubmit = (e) => {
                e.preventDefault();
                setTeachers(prev => [...prev, { ...formData, id: 'tch_' + Date.now() }]);
                setShowModal(false);
            };

            return (
                <div className="space-y-6">
                    <div className="flex justify-between items-center">
                        <h2 className="text-2xl font-black text-white">Profesores Asesores</h2>
                        <button onClick={()=>setShowModal(true)} className="bg-yellow-500 text-gray-950 font-bold px-4 py-2 rounded-xl text-sm">
                            <i className="fa-solid fa-plus mr-1"></i> Cargar Profesor
                        </button>
                    </div>

                    <div className="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
                        <table className="w-full text-left text-sm text-gray-300">
                            <thead className="bg-gray-950 text-gray-400 uppercase text-[11px] border-b border-gray-800">
                                <tr>
                                    <th className="py-3 px-4">DNI</th>
                                    <th className="py-3 px-4">Profesor</th>
                                    <th className="py-3 px-4">Institución</th>
                                    <th className="py-3 px-4">Email</th>
                                    <th className="py-3 px-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-800">
                                {teachers.map(t => {
                                    const sch = schools.find(sc => sc.id === t.schoolId);
                                    return (
                                        <tr key={t.id}>
                                            <td className="py-3 px-4 font-mono font-bold text-gray-200">{t.dni}</td>
                                            <td className="py-3 px-4 font-bold text-white">{t.name}</td>
                                            <td className="py-3 px-4 text-gray-400">{sch ? sch.name : '-'}</td>
                                            <td className="py-3 px-4 text-gray-400">{t.email}</td>
                                            <td className="py-3 px-4 text-right">
                                                <button onClick={()=>setTeachers(prev=>prev.filter(x=>x.id!==t.id))} className="text-red-400">
                                                    <i className="fa-solid fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    {showModal && (
                        <div className="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
                            <div className="bg-gray-900 border border-gray-800 rounded-2xl max-w-md w-full p-6">
                                <h3 className="text-xl font-bold text-white mb-4">Nuevo Profesor Asesor</h3>
                                <form onSubmit={handleSubmit} className="space-y-4">
                                    <div>
                                        <label className="text-xs font-bold text-gray-300 block mb-1">DNI</label>
                                        <input type="text" required value={formData.dni} onChange={e=>setFormData({...formData, dni: e.target.value})} className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white" />
                                    </div>
                                    <div>
                                        <label className="text-xs font-bold text-gray-300 block mb-1">Nombre Completo</label>
                                        <input type="text" required value={formData.name} onChange={e=>setFormData({...formData, name: e.target.value})} className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white" />
                                    </div>
                                    <div>
                                        <label className="text-xs font-bold text-gray-300 block mb-1">Institución</label>
                                        <select value={formData.schoolId} onChange={e=>setFormData({...formData, schoolId: e.target.value})} className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white">
                                            {schools.map(sc => <option key={sc.id} value={sc.id}>{sc.name}</option>)}
                                        </select>
                                    </div>
                                    <div className="flex justify-end gap-2 pt-3">
                                        <button type="button" onClick={()=>setShowModal(false)} className="px-4 py-2 text-sm text-gray-400">Cancelar</button>
                                        <button type="submit" className="bg-yellow-500 text-gray-950 font-bold px-4 py-2 rounded-xl text-sm">Guardar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}
                </div>
            );
        }

        function SchoolsView({ schools, setSchools }) {
            const [showModal, setShowModal] = useState(false);
            const [formData, setFormData] = useState({ name: '', code: '', city: '' });

            const handleSubmit = (e) => {
                e.preventDefault();
                setSchools(prev => [...prev, { ...formData, id: 'sch_' + Date.now() }]);
                setShowModal(false);
            };

            return (
                <div className="space-y-6">
                    <div className="flex justify-between items-center">
                        <h2 className="text-2xl font-black text-white">Instituciones Educativas</h2>
                        <button onClick={()=>setShowModal(true)} className="bg-yellow-500 text-gray-950 font-bold px-4 py-2 rounded-xl text-sm">
                            <i className="fa-solid fa-plus mr-1"></i> Cargar Institución
                        </button>
                    </div>

                    <div className="bg-gray-900 border border-gray-800 rounded-2xl overflow-hidden">
                        <table className="w-full text-left text-sm text-gray-300">
                            <thead className="bg-gray-950 text-gray-400 uppercase text-[11px] border-b border-gray-800">
                                <tr>
                                    <th className="py-3 px-4">Código</th>
                                    <th className="py-3 px-4">Nombre</th>
                                    <th className="py-3 px-4">Ciudad</th>
                                    <th className="py-3 px-4 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-800">
                                {schools.map(s => (
                                    <tr key={s.id}>
                                        <td className="py-3 px-4 font-mono font-bold text-yellow-400">{s.code}</td>
                                        <td className="py-3 px-4 font-bold text-white">{s.name}</td>
                                        <td className="py-3 px-4 text-gray-400">{s.city}</td>
                                        <td className="py-3 px-4 text-right">
                                            <button onClick={()=>setSchools(prev=>prev.filter(x=>x.id!==s.id))} className="text-red-400">
                                                <i className="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {showModal && (
                        <div className="fixed inset-0 bg-black/80 flex items-center justify-center p-4 z-50">
                            <div className="bg-gray-900 border border-gray-800 rounded-2xl max-w-md w-full p-6">
                                <h3 className="text-xl font-bold text-white mb-4">Nueva Institución</h3>
                                <form onSubmit={handleSubmit} className="space-y-4">
                                    <div>
                                        <label className="text-xs font-bold text-gray-300 block mb-1">Nombre</label>
                                        <input type="text" required value={formData.name} onChange={e=>setFormData({...formData, name: e.target.value})} className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white" />
                                    </div>
                                    <div>
                                        <label className="text-xs font-bold text-gray-300 block mb-1">Código</label>
                                        <input type="text" required value={formData.code} onChange={e=>setFormData({...formData, code: e.target.value})} className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white" />
                                    </div>
                                    <div>
                                        <label className="text-xs font-bold text-gray-300 block mb-1">Ciudad</label>
                                        <input type="text" required value={formData.city} onChange={e=>setFormData({...formData, city: e.target.value})} className="w-full bg-gray-950 border border-gray-800 rounded-xl px-3 py-2 text-white" />
                                    </div>
                                    <div className="flex justify-end gap-2 pt-3">
                                        <button type="button" onClick={()=>setShowModal(false)} className="px-4 py-2 text-sm text-gray-400">Cancelar</button>
                                        <button type="submit" className="bg-yellow-500 text-gray-950 font-bold px-4 py-2 rounded-xl text-sm">Guardar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}
                </div>
            );
        }

        function WordBankView({ words }) {
            const [filterLevel, setFilterLevel] = useState(1);
            const filtered = words.filter(w => w.level === filterLevel);

            return (
                <div className="space-y-6">
                    <div className="flex justify-between items-center">
                        <div>
                            <h2 className="text-2xl font-black text-white">Banco de Palabras por Nivel</h2>
                            <p className="text-gray-400 text-sm">Guía de palabras y oraciones para los jurados de competencia</p>
                        </div>
                        <div className="flex gap-2">
                            {[1, 2, 3].map(lvl => (
                                <button 
                                    key={lvl}
                                    onClick={()=>setFilterLevel(lvl)}
                                    className={`px-4 py-2 rounded-xl text-sm font-bold ${filterLevel === lvl ? 'bg-yellow-500 text-gray-950' : 'bg-gray-800 text-gray-400'}`}
                                >
                                    Level {lvl}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {filtered.map((w, i) => (
                            <div key={i} className="bg-gray-900 border border-gray-800 p-5 rounded-2xl">
                                <span className="text-[10px] uppercase font-bold text-yellow-400 bg-yellow-500/10 px-2.5 py-1 rounded">
                                    Level {w.level}
                                </span>
                                <h3 className="text-2xl font-black text-white tracking-widest mt-2">{w.word}</h3>
                                <p className="text-sm text-gray-400 mt-3 italic">"{w.sentence}"</p>
                            </div>
                        ))}
                    </div>
                </div>
            );
        }

        const rootElement = document.getElementById('root');
        const root = ReactDOM.createRoot(rootElement);
        root.render(<App />);
    </script>
</body>
</html>