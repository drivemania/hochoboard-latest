<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CUSTARD-BOARD 설치 마법사</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans KR', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center py-10">

    <div class="w-full max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden">
        <div class="bg-amber-600 p-6 text-center">
            <h1 class="text-white text-2xl font-bold mb-4">CUSTARD-BOARD 설치 마법사</h1>
            
            <div class="flex justify-center items-center space-x-2"> <?php 
                    $currentStep = $step ?? 1;
                    function getStepClass($step, $current) {
                        if ($step < $current) return "bg-green-400 text-white border-green-400";
                        if ($step == $current) return "bg-white text-amber-600 font-bold border-2 border-white";
                        return "bg-amber-500 text-amber-200 border border-amber-400";
                    }
                ?>
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm border <?php echo getStepClass(1, $currentStep); ?>">1</div>
                <div class="w-4 h-0.5 bg-amber-400"></div>
                
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm border <?php echo getStepClass(2, $currentStep); ?>">2</div>
                <div class="w-4 h-0.5 bg-amber-400"></div>

                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm border <?php echo getStepClass(3, $currentStep); ?>">3</div>
                <div class="w-4 h-0.5 bg-amber-400"></div>

                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm border <?php echo getStepClass(4, $currentStep); ?>">4</div>
            </div>
        </div>

        <div class="p-8">