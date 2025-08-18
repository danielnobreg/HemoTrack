<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HemoTrack - Extrator de Hemogramas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Estilo para o feedback do botão de copiar */
        .copied-feedback {
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 flex items-center justify-center min-h-screen p-4">

    <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-3xl">

        <header class="text-center mb-8">
            <h1 class="text-3xl font-bold text-red-600">HemoTrack</h1>
            <p class="text-gray-500 mt-2">Envie um PDF de hemograma para extrair os resultados de forma rápida.</p>
        </header>

        <!-- Formulário de Upload -->
        <form action="{{ url('/upload') }}" method="POST" enctype="multipart/form-data" class="border-b pb-8 mb-8">
            @csrf
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <label for="pdf" class="block text-sm font-medium text-gray-700 sr-only">Ficheiro PDF</label>
                <input type="file" name="pdf" id="pdf" required class="block w-full text-sm text-gray-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-full file:border-0
                    file:text-sm file:font-semibold
                    file:bg-red-50 file:text-red-700
                    hover:file:bg-red-100 transition-colors duration-200
                ">
                <button type="submit" class="w-full sm:w-auto bg-red-600 text-white font-bold py-2 px-6 rounded-full hover:bg-red-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Extrair Dados
                </button>
            </div>
            
            <!-- Checkbox para tipo de exame adicionada aqui -->
            <div class="mt-6 text-center">
                <label for="exame_tipo" class="inline-flex items-center cursor-pointer">
                    <input id="exame_tipo" name="exame_tipo" type="checkbox" value="on" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500" checked>
                    <span class="ml-3 text-sm font-medium text-gray-700">Processar como Hemograma Completo</span>
                </label>
            </div>

            @if($errors->any())
                <div class="mt-4 text-red-500 text-sm text-center">
                    {{ $errors->first() }}
                </div>
            @endif
        </form>

        <!-- Secção de Resultados -->
        @if(isset($output))
            <div class="space-y-6">
                <div>
                    <h2 class="text-xl font-semibold mb-3">Resultado Formatado</h2>
                    <div class="relative">
                        <!-- O botão de copiar foi adicionado aqui -->
                        <button id="copyButton" title="Copiar para a área de transferência" class="absolute top-2 right-2 p-2 bg-gray-200 text-gray-600 rounded-full hover:bg-gray-300 transition-colors duration-200">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard" viewBox="0 0 16 16">
                                <path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1v-1z"/>
                                <path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5h3zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3z"/>
                            </svg>
                            <div id="copyFeedback" class="copied-feedback">Copiado!</div>
                        </button>
                        <textarea id="outputText" readonly class="w-full h-48 p-4 bg-gray-50 border border-gray-200 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-red-500">{{ trim($output) }}</textarea>
                    </div>
                </div>

                <!-- Secção de Texto Cru (para depuração) -->
                @if(isset($raw))
                    <div>
                        <h3 class="text-lg font-semibold mb-2">Texto Cru Extraído do PDF</h3>
                        <pre class="w-full h-64 p-4 bg-gray-900 text-white text-xs rounded-lg overflow-auto font-mono">{{ $raw }}</pre>
                    </div>
                @endif
            </div>
        @endif

    </div>

    <script>
        // Lógica para o botão de copiar
        const copyButton = document.getElementById('copyButton');
        const outputText = document.getElementById('outputText');
        const copyFeedback = document.getElementById('copyFeedback');

        if (copyButton && outputText) {
            copyButton.addEventListener('click', function () {
                // Seleciona o texto na textarea
                outputText.select();
                outputText.setSelectionRange(0, 99999); // Para dispositivos móveis

                try {
                    // Tenta copiar o texto para a área de transferência
                    // document.execCommand é usado para maior compatibilidade em iframes
                    var successful = document.execCommand('copy');
                    if (successful) {
                        // Mostra o feedback visual
                        copyFeedback.style.opacity = '1';
                        // Esconde o feedback após 2 segundos
                        setTimeout(function() {
                            copyFeedback.style.opacity = '0';
                        }, 2000);
                    }
                } catch (err) {
                    console.error('Não foi possível copiar o texto: ', err);
                }

                // Remove a seleção do texto
                window.getSelection().removeAllRanges();
            });
        }
    </script>

</body>
</html>
