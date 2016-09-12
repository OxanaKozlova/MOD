<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Ob\HighchartsBundle\Highcharts\Highchart;

class DefaultController extends Controller
{

    private $array = [];
    const N = 20000;
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {

        $data_form = array();
        $form = $this->createFormBuilder($data_form)
            ->add('R0', TextType::class)
            ->add('A', TextType::class)
            ->add('M', TextType::class)
            ->add('save', SubmitType::class, array('label' => 'Random'))
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            $data = $form->getData();
            $this->createRandom((integer)$data['R0'], (integer)$data['A'], (integer)$data['M']);

            $response = $this->forward('AppBundle:Default:showBarChart', array(
                'random'  => $this->array,
            ));
            //return $this->redirectToRoute('bar_chart', array("random" => 5));
            return$response;
        }

        return $this->render('default/index.html.twig', [
                'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/bar_chart", name="bar_chart")
     */
    public function showBarChartAction(Request $request, $random)
    {

        $series = array(
            array(
                "data" => $this->createBarChart($random),
                'color' => '#008080')
        );

        $ob = new Highchart();
        $ob->chart->renderTo('linechart');  // The #id of the div where to render the chart
        $ob->chart->type('column');

        $ob->xAxis->categories($this->getBoundaryValue($random));
        $ob->title->text('Гистограмма');
        $ob->series($series);




        return $this->render('default/barChart.html.twig',
            ['random' => $random,
                'chart' => $ob,
                'expected_value'=> $this->getExpectedValue($random),
                'dispersion' => $this->getDispersion($random),
                'is_uniform' => $this->isUniform($random),
                'period' => $this->getPeriod($random)]);

    }

    private function createBarChart($random_array)
    {
        $k = 20;
        $min = min($random_array);
        $max = max($random_array);
        $r = $max - $min;
        $bar_chart = [];
        $delta = (float)$r / (float)$k;
        if($delta == 0){
            $bar_chart[] = count($random_array);
            return $bar_chart;
        }

        $boundary_value = [];
        for($i = $min; $i <= $max; $i += $delta){
            $boundary_value[] = $i;
        }
        $boundary_value[] = $max;


        for($i = 0; $i <= $k; $i++){
            $bar_chart[] = 0;
        }


        for($i = 1; $i < count($boundary_value); $i++){
            for($j = 0; $j <count($random_array); $j++){

                if($random_array[$j] <= $boundary_value[$i] && $random_array[$j] >= $boundary_value[$i-1]){
                    $bar_chart[$i-1] ++;

                }
            }
        }
      //  unset($bar_chart[count($bar_chart)-1]);
        for ($i = 0; $i< count($bar_chart); $i++){
            $bar_chart[$i] /= count($random_array);
        }
        return $bar_chart;

    }

    private function getExpectedValue($random_array){
        $expectedValue = 0;
        for($i = 0; $i<count($random_array); $i++){
            $expectedValue +=$random_array[$i];
        }
        return $expectedValue/count($random_array);

    }

    public  function getDispersion($random_array)
    {
        $dispersion = 0;
        $count = count($random_array);
        for($i = 0; $i<$count; $i++){
            $dispersion += ($random_array[$i] - $this->getExpectedValue($random_array)) ** 2;

        }
         return $dispersion / count($random_array);
    }

    private function getBoundaryValue($random){
        $k = 20;
        $min = min($random);
        $max = max($random);
        $boundary_value = [];
        $r = $max - $min;

        $delta = (float)$r / (float)$k;
        if($delta == 0) {
            $boundary_value[] = min($random);
            return $boundary_value;
        }
        for($i = $min; $i <= $max; $i += $delta){
            $boundary_value[] = $i;
        }
        $boundary_value[] = $max;

        return $boundary_value;
    }


    private function isUniform($random_array)
    {
        $k = 0;
        for($i = 1; $i < count($random_array); $i+=2){
            if((($random_array[$i-1] ** 2) + ($random_array[$i] ** 2)) < 1){
                $k++;
            }
        }

        return 2*$k/self::N;

    }
    private function createRandom($r0, $a, $m, $count = 0)
    {

        $count ++;
        $rn = (($r0*$a) % $m);


        $this->array[] = $rn / $m;
        if(count($this->array) > self::N){
            return $this->array;
        }


        $this->createRandom($rn, $a, $m, $count);
    }

    private function getPeriod($random_array)
    {
        $xv = $random_array[(count($random_array)-1)]; //поменять на 50000
        $i1 = $i2 = 0;

        for($i = 0; $i < count($random_array); $i++) {
            if ($xv == $random_array[$i]) {
                $i1 = $i;
                break;
            }
        }
        for($i = ($i1+1); $i < count($random_array); $i++){
            if($xv == $random_array[$i]){
                $i2 = $i;
                break;
            }
        }


        $p = 0;
        if($i1 < $i2){
            $p = $i2 - $i1;
        }

        $i3 = 0;

        for($i = 0; $i < count($random_array); $i ++){
           if( ($i + $p) < count($random_array) && $random_array[$i] == $random_array[$i + $p]){
               $i3 = $i;
               break;
           }
        }

        $l = $i3 + $p;
        return $l;


    }


}
