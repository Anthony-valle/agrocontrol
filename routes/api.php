use Illuminate\Support\Facades\Route;
use App\Models\Cultivo;

Route::get('/lotes/hectareas-ocupadas', function() {
    $data = Cultivo::selectRaw('lotes_id, SUM(hectareas) as total')
        ->groupBy('lotes_id')
        ->pluck('total', 'lotes_id');
    return response()->json($data);
});
