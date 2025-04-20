@extends('base')
@section('content')

    <div class='loader'>
        <div class='spinner-grow text-danger' role='status'>
            <span class='sr-only'>Loading...</span>
        </div>
    </div>
<div class="page-content">
    <div class="main-wrapper">
        <div class="row">
            <div class="col-3">
                <div class="card folder">
                    <div class="card-body">
                        <div class="folder-icon">
                            <i class="fa fa-chart-bar"></i>
                        </div>
                        <div class="folder-info">
                            <a href="#"><h6>Volume transaction</h6></a>
                            <span><h5>{{number_format($volumeofTransaction, 0, ',', ' ')." ".$money}}</h5></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card folder" style="background-color: #69F0AE">
                    <div class="card-body">
                        <div class="folder-icon">
                            <i class="fa fa-wallet" style="color: white"></i>
                        </div>
                        <div class="folder-info">
                            <a href="#"><h6>Current balance</h6></a>
                            <span><h5>{{number_format($currentBalance, 0, ',', ' ')." ".$money}}</h5></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card folder">
                    <div class="card-body">
                        <div class="folder-icon">
                            <i class="fa fa-credit-card"></i>
                        </div>
                        <div class="folder-info">
                            <a href="#"><h6>Revenue</h6></a>
                            <span><h5>{{number_format($revenue, 0, ',', ' ')." ".$money}}</h5></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card folder">
                    <div class="card-body">
                        <div class="folder-icon">
                            <i class="fa fa-users"></i>
                        </div>
                        <div class="folder-info">
                            <a href="#"><h6>Agents</h6></a>
                            <span><h5>{{number_format($agent, 0, ',', ' ')}}</h5></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="row">
            <div class="col-sm-6 col-xl-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Performance</h5>
                        <div id="apex3"></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4">
                <div class="card stat-widget">
                    <div class="card-body">
                        <h5 class="card-title">Best agents</h5>
                        @isset($bestAgents)

                            @if($bestAgents->isNotEmpty())
                                <?php
                                     function GetColor(){
                                        $color = "#";
                                        for($i = 0; $i < 6; $i++){
                                            $color .= rand(0,9);
                                        }
                                        return $color;
                                    }
                                     function GetIcon(){
                                        $icon = "";
                                        $icons = ["thumbs-up","thumbs-down","credit-card","user","shopping-cart"];
                                        $icon = $icons[rand(0,4)];
                                        return $icon;
                                    }
                                function GetColorText(){

                                    $textColors = ["text-primary","text-warning","text-success","text-secondary","txt-danger"];
                                    $textColor = $textColors[rand(0,4)];
                                    return $textColor;
                                }

                                ?>
                                @foreach($bestAgents as $best)

                                    <?php
                                        $textColors = GetColorText();
                                    ?>
                                    <div class="transactions-list">
                                        <div class="tr-item">
                                            <div class="tr-company-name">
                                                <div class="tr-icon tr-card-icon tr-card-bg-primary <?php echo $textColors; ?>">
                                                    <i data-feather="user"></i>
                                                </div>
                                                <div class="tr-text">
                                                    <h4 title="{{$best->login}}">{{$best->name." ".$best->surname}}</h4>
                                                    @if(Auth::user()->type_user_id == \App\Http\Enums\UserRolesEnum::DISTRIBUTEUR->value)
                                                         <p>{{$best->login}}</p>
                                                    @else
                                                        <p>{{$best->name_distributeur}}</p>
                                                    @endif

                                                </div>
                                            </div>
                                            <div class="tr-rate">
                                                <p>

                                                <div class="tr-text">
                                                    <h4 title="Turnover">{{number_format($best->volume,0,","," ")." ".$money}}</h4>
                                                    <p title="Commission">{{number_format($best->commission,0,","," ")." ".$money}}</p>
                                                </div>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                            @endif
                        @endisset

                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 col-lg-12">
                <div class="card table-widget">
                    <div class="card-body">
                        <h5 class="card-title">Recent transactions</h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th scope="col">Service</th>
                                    <th scope="col">TransactionID</th>
                                    <th scope="col">PartenerID</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Amount</th>
                                    <th scope="col">Agent</th>
                                </tr>
                                </thead>
                                <tbody>
                                     @isset($lastTransactions)
                                        @if($lastTransactions->isNotEmpty())
                                            @foreach($lastTransactions as $c)
                                                <tr>
                                                    <th scope="row"><img src="{{asset("assets/partenaires/".$c->service->logo_service)}}" alt="">{{$c->service->name_service}}</th>
                                                    <td>#{{$c->reference}}</td>
                                                    <td>#{{$c->reference_partenaire}}</td>
                                                    <td>{{$c->date_transaction}}</td>
                                                    <td>{{$c->customer_phone}}</td>
                                                    @if($c->debit==0)
                                                        <td><span class="badge bg-success">{{number_format($c->credit,0,","," ")." ".$money}}</span></td>
                                                    @else
                                                        <td><span class="badge bg-danger">{{number_format($c->debit,0,","," ")." ".$money}}</span></td>
                                                    @endif

                                                    <td>{{$c->auteur->name}}</td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    @endisset

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

<script src="{{asset("assets/plugins/jquery/jquery-3.4.1.min.js")}}"></script>
<script src="{{asset("assets/plugins/jquery/jquery-3.4.1.min.js")}}"></script>
<script src="{{asset("assets/js/popper.min.js")}}"></script>
<script src="{{asset("assets/js/feather.min.js")}}"></script>
<script src="{{asset("assets/plugins/perfectscroll/perfect-scrollbar.min.js")}}"></script>
<script src="{{asset("assets/plugins/apexcharts/apexcharts.min.js")}}"></script>
<script language="javascript">
    $(document).ready(function () {
        var options1 = {
            chart: {
                height: 350,
                type: 'line',
                toolbar: {
                    show: true,
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth'
            },
            colors: ['#90e0db','#e1b5c2'],
            series: [{
                name: 'Retrait',
                data: @isset($retrait)
                    {{$retrait}}
                    @endisset
            }, {
                name: 'Dépôt',
                data: @isset($envoi)
                    {{$envoi}}
                    @endisset
            }],

            xaxis: {
                type: 'month',
                categories: ["Jan.", "Feb.", "Mar.", "Apr.", "May", "Jun.", "Jul.", "Aug.", "Sep.", "Oct.", "Nov.", "Dec."],
                labels: {
                    style: {
                        colors: 'rgba(94, 96, 110, .5)'
                    }
                }
            },
            grid: {
                borderColor: 'rgba(94, 96, 110, .5)',
                strokeDashArray: 4
            }
        }
        var chart1 = new ApexCharts(
            document.querySelector("#apex3"),
            options1
        );

        chart1.render();

    });

</script>
@endsection

