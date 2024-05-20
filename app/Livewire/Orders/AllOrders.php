<?php

namespace App\Livewire\Orders;

use App\Helpers\UsefulHelpers;
use App\Livewire\Traits\SideModalAction;
use App\Models\OrderLog;
use App\Models\OrderStatus;
use App\Services\WordSuffixService;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use PhpOffice\PhpWord\TemplateProcessor;

#[On(['orderAdded','orderWasDeleted'])]
class AllOrders extends Component
{
    use WithPagination,SideModalAction,AuthorizesRequests;
    public $selectedOrder;
    #[Url]
    public $status;
    #[Url]
    public $search = [];

    #[On('selectOrder')]
    public function selectOrder($id)
    {
        $this->selectedOrder = $id;
    }

    public function setStatus($newStatus)
    {
        $this->status = $newStatus;
        $this->resetPage();
    }

    public function fillFilter()
    {
        $this->status = request()->query('status')
            ? request()->query('status')
            : 'all';
    }

    public function resetFilter()
    {
        $this->reset('search');
        $this->resetPage();
    }

    public function setDeleteOrder($order_no)
    {
        $this->dispatch('setDeleteOrder',$order_no);
    }

    public function restoreData($order_no)
    {
        $orderLog = OrderLog::withTrashed()->where('order_no',$order_no)->first();
        $orderLog->restore();
        $orderLog->update([
            'deleted_by' => null
        ]);
        $this->dispatch('orderAdded',__('Order was updated successfully!'));
    }

    public function forceDeleteData($order_no)
    {
        $model = OrderLog::withTrashed()->where('order_no',$order_no)->first();

        $model->handleDeletion();

        $this->dispatch('orderWasDeleted' , __('Order was deleted!'));
    }

    public function printOrder(string $order_no)
    {
        $order = OrderLog::with(['order','components','attributes'])->where('order_no',$order_no)->first();

        $templateProcessor = new TemplateProcessor('storage/'.$order->order->content);
        $templateProcessor->setValue('day', Carbon::parse($order->given_date)->format('d'));
        $templateProcessor->setValue('month', Carbon::parse($order->given_date)->locale('AZ')->monthName);
        $templateProcessor->setValue('year', Carbon::parse($order->given_date)->format('Y'));
        $templateProcessor->setValue('rank_director', $order->given_by_rank);
        $templateProcessor->setValue('name_director', $order->given_by);

        // export to word file
        $_component_texts = $order->components->pluck('content')->toArray();
        $replacements = [];
        $suffixService = new WordSuffixService();
        foreach ($order->components as $k => $_component)
        {
            $attr_list = $order->attributes
                                ->where('component_id',$_component->id)
                                ->value('attributes');

            $_replace_texts[] = UsefulHelpers::modifyArrayToKeyValuePair($attr_list);
            $_replace_texts[$k]['$year'] .= $suffixService->getNumberSuffix($_replace_texts[$k]['$year']);
            $_replace_texts[$k]['$surname'] = $suffixService->getSurnameSuffix( $_replace_texts[$k]['$surname']);
            $_replace_texts[$k]['$structure_main'] = $suffixService->getStructureSuffix( $_replace_texts[$k]['$structure_main']);
            // **** her hansi bir hisseni bold etmek ucun
            $_replace_texts[$k]['$fullname'] = $this->convertWordIntoBold($_replace_texts[$k]['$fullname']);
        }

        foreach ($_component_texts as $key => &$text)
        {
            $text = str_replace(array_keys($_replace_texts[$key]), array_values($_replace_texts[$key]), $text);

            $replacements[] = [
                'content_text' => ( $key + 1 ). '. '.str_replace("\n","<w:br/>", $text),
            ];
        }

        $templateProcessor->replaceBlock('newline',PHP_EOL);
        $templateProcessor->cloneBlock('content',0,true,false,$replacements);
        // end export to word file

        $filename = "{$order->order->name}_".Carbon::now()->format('d.m.Y H:i:s');
        $templateProcessor->saveAs($filename. '.docx');
        return response()->download($filename. '.docx')->deleteFileAfterSend(true);
    }

    private function convertWordIntoBold(string $word)
    {
        return '<w:rPr><w:b w:val="true"/></w:rPr>'
            . $word
            . '<w:rPr><w:b w:val="false"/></w:rPr>';
    }

    protected function returnData($type = "normal")
    {
        $result = OrderLog::with(['components','status','personDidDelete','creator'])
            ->filter($this->search ?? [])
            ->when(!empty($this->selectedOrder),function ($q){
                $q->where('order_id',$this->selectedOrder);
            })
            ->when(is_int($this->status) && $this->status > 0 ,function($q)
            {
                $q->where('status_id',$this->status);
            })
            ->when($this->status == 'deleted',function($q)
            {
                $q->onlyTrashed();
            })
            ->orderByDesc('given_date');

        return $type == "normal"
            ? $result->paginate(20)->withQueryString()
            : $result->get()->toArray();
    }

    public function mount()
    {
        $this->fillFilter();
        $this->selectedOrder = $this->selectedOrder ?? request()->query('selectedOrder');
    }

    public function render()
    {
        $orders = $this->returnData();

        $statuses = OrderStatus::where('locale',config('app.locale'))->get();

        return view('livewire.orders.all-orders',compact('orders','statuses'));
    }
}
