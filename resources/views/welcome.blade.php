<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Clawra') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style>
                body {
                    font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
                    background-color: #FDFDFC;
                    color: #1b1b18;
                    margin: 0;
                    padding: 0;
                }
                
                .container {
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 2rem;
                    text-align: center;
                }
                
                .header {
                    margin-bottom: 2rem;
                }
                
                .header h1 {
                    font-size: 2.5rem;
                    font-weight: 600;
                    margin-bottom: 1rem;
                    color: #f53003;
                }
                
                .header p {
                    font-size: 1.2rem;
                    color: #706f6c;
                    margin-bottom: 2rem;
                }
                
                .cta-button {
                    display: inline-block;
                    padding: 1rem 2rem;
                    background-color: #f53003;
                    color: white;
                    text-decoration: none;
                    border-radius: 6px;
                    font-weight: 500;
                    font-size: 1.1rem;
                    transition: background-color 0.2s;
                }
                
                .cta-button:hover {
                    background-color: #d42a02;
                }
                
                .features {
                    display: flex;
                    flex-wrap: wrap;
                    justify-content: center;
                    gap: 2rem;
                    margin: 3rem 0;
                }
                
                .feature {
                    flex: 1;
                    min-width: 200px;
                    padding: 1.5rem;
                    background-color: white;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                }
                
                .feature h3 {
                    font-size: 1.3rem;
                    margin-bottom: 1rem;
                    color: #f53003;
                }
                
                .feature p {
                    color: #706f6c;
                }
                
                .footer {
                    margin-top: 3rem;
                    color: #706f6c;
                    font-size: 0.9rem;
                }
            </style>
        @endif
    </head>
    <body class="bg-[#FDFDFC] dark:bg-[#0a0a0a] text-[#1b1b18]">
        <div class="container">
            <div class="header">
                <h1>Clawra AI Coordinator</h1>
                <p>Your intelligent assistant for software development tasks</p>
                
                <a href="/coordinator" class="cta-button">Start Coordinating</a>
            </div>
            
            <div class="features">
                <div class="feature">
                    <h3>Research</h3>
                    <p>Gather information and insights on any topic</p>
                </div>
                
                <div class="feature">
                    <h3>Planning</h3>
                    <p>Create detailed project plans and task breakdowns</p>
                </div>
                
                <div class="feature">
                    <h3>Development</h3>
                    <p>Generate code and implement features</p>
                </div>
            </div>
            
            <div class="features">
                <div class="feature">
                    <h3>Testing</h3>
                    <p>Create comprehensive unit and feature tests</p>
                </div>
                
                <div class="feature">
                    <h3>Review</h3>
                    <p>Code review and quality assurance</p>
                </div>
                
                <div class="feature">
                    <h3>Coordination</h3>
                    <p>Orchestrate multiple agents for complex tasks</p>
                </div>
            </div>
            
            <div class="footer">
                <p>Clawra AI Assistant © {{ date('Y') }}</p>
            </div>
        </div>
    </body>
</html>