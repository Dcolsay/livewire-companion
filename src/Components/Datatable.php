<?php declare(strict_types=1);

namespace Codedge\LivewireCompanion\Components;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Livewire\WithPagination;

class Datatable extends Component
{
    use WithPagination;

    public int $perPage = 10;
    public array $perPageOptions = [ 5, 10, 25 ];

    /**
     * @var string
     */
    public $sortField;
    public bool $sortAsc = true;
    public bool $sortingEnabled = true;

    public string $searchTerm = '';
    protected string $searchField = '';
    public bool $searchingEnabled = true;

    protected string $template = 'vendor.livewire-companion.datatable';

    /**
     * @var mixed
     */
    protected $model;

    /**
     * All secure items
     *
     * @var \Illuminate\Database\Eloquent\Collection|\Illuminate\Support\Collection
     */
    protected $items;

    public function render()
    {
        $this->loadModel();

        if(!in_array($this->perPage, $this->perPageOptions)) {
            throw new \Exception('Per page option is not within the available values.');
        }

        $items = $this->items->paginate($this->perPage);

        return view($this->template, compact('items'));
    }

    private function loadModel(): void
    {
        $instance = new $this->model;

        if($this->canSearch()) {
            $instance = $instance::where($this->searchField, 'LIKE', '%'.$this->searchTerm.'%');
        }

        if($instance instanceof Builder) {
            $this->items = $this->handleBuilder($instance);
        } else {
            $this->items = $this->handleCollection($instance);
        }
    }

    private function handleCollection($instance)
    {
        $sortDirectionMethod = $this->sortAsc ? 'sortBy' : 'sortByDesc';
        $collection = $instance->all();

        if($this->canSearch()) {
            $collection = $collection->where($this->searchField, $this->searchTerm);
        }

        return collect($collection->$sortDirectionMethod($this->sortField)->all());
    }

    private function handleBuilder(Builder $instance)
    {
        return $instance->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                        ->get();
    }

    private function canSearch(): bool
    {
        return $this->searchingEnabled && !empty($this->searchTerm) && !empty($this->searchField);
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortAsc = ! $this->sortAsc;
        } else {
            $this->sortAsc = true;
        }

        $this->sortField = $field;
    }

    public function paginationView()
    {
        return 'livewire-companion::pagination-links';
    }

    public function updating($name, $value)
    {
        // Reset page to 1 when doing a search
        if($name === 'searchTerm') {
            $this->paginator['page'] = 1;
        }
    }
}
