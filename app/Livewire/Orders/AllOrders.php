<?php

namespace App\Livewire\Orders;

use App\Helpers\UsefulHelpers;
use App\Livewire\Traits\SideModalAction;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\OrderStatus;
use App\Models\Structure;
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
        $givenDate = Carbon::parse($order->given_date);

        $templateProcessor = new TemplateProcessor('storage/'.$order->order->content);
        $templateProcessor->setValue('day', $givenDate->format('d'));
        $templateProcessor->setValue('month',$givenDate->locale('AZ')->monthName);
        $templateProcessor->setValue('year', $givenDate->format('Y'));
        $templateProcessor->setValue('rank_director', $order->given_by_rank);
        $templateProcessor->setValue('name_director', $order->given_by);
        // export to word file
        $_component_texts = $order->attributes->load('component')->groupBy('row_number')->map(function ($group) {
            $component = $group->first()->component;
            return [
                'title' => $component->title,
                'content' => $group->pluck('component.content')->toArray(),
            ];
        })->toArray();

        $replacements = [];
        $suffixService = new WordSuffixService();
        $_replace_texts = [];

        foreach ($order->components as $componentIndex => $_component) {
            $attr_list = $order->attributes
                        ->where('component_id', $_component->id)
                        ->where('row_number', $componentIndex)
                        ->pluck('attributes')
                        ->toArray();

            // pluck edib dovre salmaq olar.
            foreach ($attr_list as $attrIndex => $attr) {
                $keyReplaced = $componentIndex + $attrIndex;
                $_replace_texts[] = UsefulHelpers::modifyArrayToKeyValuePair($attr);

                if ($order->order->blade == Order::BLADE_DEFAULT) {
                    $_replace_texts[$keyReplaced]['$year'] .= $suffixService->getNumberSuffix((int)$_replace_texts[$keyReplaced]['$year']);
                    $_replace_texts[$keyReplaced]['$surname'] = $suffixService->getSurnameSuffix($_replace_texts[$keyReplaced]['$surname']);
                    $_replace_texts[$keyReplaced]['$structure_main'] = $suffixService->getStructureSuffix($_replace_texts[$keyReplaced]['$structure_main']);
                    $_replace_texts[$keyReplaced]['$fullname'] = $this->convertWordIntoBold($_replace_texts[$keyReplaced]['$fullname']);
                } elseif ($order->order->blade == Order::BLADE_VACATION) {
                    $_replace_texts[$keyReplaced]['$start_date'] .= $suffixService->getNumberSuffix(Carbon::parse($_replace_texts[$keyReplaced]['$start_date'])->year);
                    $_replace_texts[$keyReplaced]['$end_date'] .= $suffixService->getNumberSuffix(Carbon::parse($_replace_texts[$keyReplaced]['$end_date'])->year);
                    $_replace_texts[$keyReplaced]['$structure'] = $this->getFullStructureNameWithSuffixes($_replace_texts[$keyReplaced]['$structure'],$suffixService);
                    $_replace_texts[$keyReplaced]['$position'] = $suffixService->getMultiSuffix($_replace_texts[$keyReplaced]['$position'],multi:false);
                }
            }
        }

        foreach ($_component_texts as $key => &$text)
        {
            $title = str_replace(array_keys($_replace_texts[$key]), array_values($_replace_texts[$key]), $text['title']);
            $content = '';
            foreach ($text['content'] as $keyContent => $contentData) {
                $replaceKey = $key + $keyContent;
                $replacedContent = str_replace(
                    array_keys($_replace_texts[$replaceKey]),
                    array_values($_replace_texts[$replaceKey]),
                    $contentData
                );

                $replacedContent = $order->order->blade == Order::BLADE_VACATION
                                ?  '<w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial" w:cs="Arial"/></w:rPr>' . $replacedContent . '<w:br/>'
                                :  $replacedContent;

                $content .= $replacedContent;
            }

            $content = str_replace("<w:br/>", '</w:t><w:br/><w:tab/><w:t xml:space="preserve">', $content );

            $text = !empty($title) ? $this->convertWordIntoBold($title)  . PHP_EOL . "<w:p/>"  . $content  : $content;

            $replacements[] = [
                    'content_text' => match($order->order->blade) {
                    'vacation' => str_replace("\n", "<w:br/>", $text),
                    'default' => ($key + 1) . '. ' . str_replace("\n", "<w:br/>", $text),
                },
            ];
        }

        $templateProcessor->replaceBlock('newline',PHP_EOL);
        $templateProcessor->cloneBlock('content',0,true,false,$replacements);
        // end export to word file

        $filename = "{$order->order->name}_".Carbon::now()->format('d.m.Y H:i:s');
        $templateProcessor->saveAs($filename. '.docx');
        return response()->download($filename. '.docx')->deleteFileAfterSend(true);
    }

    protected function getFullStructureNameWithSuffixes($name,$service)
    {
        $structureModel = Structure::where('name',$name)->first();
        $structureFullName = $structureModel->getAllParentName(isCoded: true);
        $result = '';
        foreach ($structureFullName as $structure) {
            $result.= ($service->getStructureSuffix($structure,mainStructure:true) . ' ');
        }
        return rtrim($result);
    }

    private function convertWordIntoBold(string $word)
    {
        return '<w:rPr><w:b w:val="true"/></w:rPr>'
            . $word
            . '<w:rPr><w:b w:val="false"/></w:rPr>';
    }

    protected function returnData($type = "normal")
    {
        $result = OrderLog::with(['components','status','personDidDelete','creator','orderType'])
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
