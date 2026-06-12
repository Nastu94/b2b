<x-guest-layout>
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-6 sm:p-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Privacy e Compliance</h1>
                
                <div class="prose max-w-none text-gray-600">
                    <p class="mb-4">
                        In questa sezione è possibile consultare il documento relativo alla Privacy e Compliance.
                    </p>
                    
                    <a href="{{ route('legal.vendor.file', ['filename' => 'privacy-e-compliance.pdf']) }}" target="_blank" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                        Visualizza / scarica PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
