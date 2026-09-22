<?php
namespace App\Models;

use App\Core\Model;

/**
 * مودل التوصيات الذكية
 * يقوم بتحليل البيانات وتقديم توصيات مبنية على الذكاء الاصطناعي
 */
class RecommendationModel extends Model {

    /**
     * توليد توصيات ذكية بناءً على تحليل الأداء
     */
    public function generateSmartRecommendations($analysisData, $entityType, $employeeCount) {
        $recommendations = [];
        
        // تحليل نقاط القوة
        $strengthRecommendations = $this->analyzeStrengths($analysisData);
        $recommendations = array_merge($recommendations, $strengthRecommendations);
        
        // تحليل نقاط الضعف
        $weaknessRecommendations = $this->analyzeWeaknesses($analysisData);
        $recommendations = array_merge($recommendations, $weaknessRecommendations);
        
        // توصيات استراتيجية
        $strategicRecommendations = $this->generateStrategicRecommendations($analysisData, $entityType, $employeeCount);
        $recommendations = array_merge($recommendations, $strategicRecommendations);

        return $this->prioritizeRecommendations($recommendations);
    }

    private function analyzeStrengths($analysisData) {
        $recommendations = [];
        $strongCompetencies = $analysisData['classified_performance']['strong'] ?? [];

        if (count($strongCompetencies) > 0) {
            $topStrengths = array_slice($strongCompetencies, 0, 3);
            
            foreach ($topStrengths as $strength) {
                $recommendations[] = [
                    'type' => 'strength',
                    'priority' => 'high',
                    'title' => 'تعزيز التميز في ' . $strength['section_name'],
                    'description' => 'نقترح استغلال هذا التميز في ' . $strength['section_name'] . 
                                   ' (متوسط الأداء: ' . $strength['average_score'] . '%) لنقل المعرفة وتدريب باقي الفريق.',
                    'action' => 'نقل المعرفة - ورش عمل - توثيق أفضل الممارسات',
                    'impact' => 'عالية',
                    'effort' => 'منخفضة'
                ];
            }
        }

        return $recommendations;
    }

    private function analyzeWeaknesses($analysisData) {
        $recommendations = [];
        $weakCompetencies = $analysisData['classified_performance']['weak'] ?? [];

        if (count($weakCompetencies) > 0) {
            // ترتيب من الأسوأ إلى الأقل سوءاً
            usort($weakCompetencies, function($a, $b) {
                return $a['average_score'] <=> $b['average_score'];
            });

            $criticalWeaknesses = array_slice($weakCompetencies, 0, 3);
            
            foreach ($criticalWeaknesses as $weakness) {
                $recommendations[] = [
                    'type' => 'improvement',
                    'priority' => 'critical',
                    'title' => 'معالجة فجوة الأداء في ' . $weakness['section_name'],
                    'description' => 'هناك حاجة ملحة لتحسين الأداء في ' . $weakness['section_name'] . 
                                   ' حيث يبلغ متوسط الأداء ' . $weakness['average_score'] . '% فقط.',
                    'action' => 'تدريب مكثف - متابعة فردية - توفير موارد مساعدة',
                    'impact' => 'عالية جداً',
                    'effort' => 'متوسطة'
                ];
            }
        }

        return $recommendations;
    }

    private function generateStrategicRecommendations($analysisData, $entityType, $employeeCount) {
        $recommendations = [];
        $stats = $analysisData['stats'] ?? [];
        
        $overallPerformance = $stats['average_performance'] ?? 0;
        $strongCount = $stats['strong_competencies_count'] ?? 0;
        $weakCount = $stats['weak_competencies_count'] ?? 0;

        // توصيات بناءً على الأداء العام
        if ($overallPerformance < 60) {
            $recommendations[] = [
                'type' => 'strategic',
                'priority' => 'high',
                'title' => 'خطة تحسين شاملة للأداء',
                'description' => 'الأداء العام (' . $overallPerformance . '%) أقل من المستوى المطلوب. نوصي بوضع خطة تحسين استراتيجية تشمل جميع الجوانب.',
                'action' => 'مراجعة الهيكل التنظيمي - تحليل فجوات المهارات - برامج تحفيزية',
                'impact' => 'شاملة',
                'effort' => 'عالية'
            ];
        }

        // توصيات بناءً على توزيع الكفاءات
        if ($weakCount > $strongCount) {
            $recommendations[] = [
                'type' => 'balance',
                'priority' => 'medium',
                'title' => 'تحقيق التوازن في المهارات',
                'description' => 'عدد الكفاءات الضعيفة (' . $weakCount . ') أكثر من الكفاءات القوية (' . $strongCount . '). نوصي ببرنامج متوازن للتدريب والتطوير.',
                'action' => 'تحليل احتياجات التدريب - برامج تطوير متوازنة - تخصيص الموارد',
                'impact' => 'متوسطة',
                'effort' => 'متوسطة'
            ];
        }

        // توصيات بناءً على حجم الكيان
        if ($employeeCount > 50) {
            $recommendations[] = [
                'type' => 'scale',
                'priority' => 'medium',
                'title' => 'تحسين كفاءة العمليات على نطاق واسع',
                'description' => 'مع ' . $employeeCount . ' موظف، نوصي بأتمتة عمليات التقييم وتحليل البيانات لتحسين الكفاءة.',
                'action' => 'أتمتة التقارير - تحليلات تنبؤية - نظام إشعارات ذكي',
                'impact' => 'عالية',
                'effort' => 'مرتفعة'
            ];
        }

        return $recommendations;
    }

    private function prioritizeRecommendations($recommendations) {
        $priorityWeights = [
            'critical' => 4,
            'high' => 3,
            'medium' => 2,
            'low' => 1
        ];

        usort($recommendations, function($a, $b) use ($priorityWeights) {
            $aWeight = $priorityWeights[$a['priority']] ?? 0;
            $bWeight = $priorityWeights[$b['priority']] ?? 0;
            return $bWeight - $aWeight;
        });

        return array_slice($recommendations, 0, 5); // أعلى 5 توصيات
    }
}