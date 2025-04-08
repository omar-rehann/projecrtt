
<?php
session_start();
include_once 'autoloader.inc.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;




if (isset($_GET['uploadImage'])) {
  $up = uploadFile($_FILES['file']['tmp_name']);
  echo '../style/images/uploads/' . $up . '.jpg';
}elseif (isset($_GET['deleteImage'])) {
    deleteImage($_POST['src']);
    echo 'success';
}elseif (isset($_GET['deleteAnswer'])){
  $q = new question;
  if(is_numeric($_POST['ansID'])){
    $q->deleteAnswer($_POST['ansID']);
    echo 'success';
  }
}elseif (isset($_GET['addQuestion'])){
    $question = isset($_POST['questionText']) ? trim($_POST['questionText']) : null;
    $qtype = isset($_POST['qtype']) ? trim($_POST['qtype']) : null;
    $isTrue = isset($_POST['isTrue']) ? trim($_POST['isTrue']) : 0;
    $points = isset($_POST['points']) ? trim($_POST['points']) : 0;
    $difficulty = isset($_POST['difficulty']) ? trim($_POST['difficulty']) : 1;
    $course = $_POST['Course'];
    if($question == null){
      $_SESSION["error"][] = 'Question Can\' Be Empty';
      header('Location: ' . $_SERVER['HTTP_REFERER']);exit;
    }elseif($qtype == null){
      $_SESSION["error"][] = 'Question Type is not selected';
      header('Location: ' . $_SERVER['HTTP_REFERER']);exit;
    }

    $newQuestion = new question;
    $newQuestion->insertQuestion($question,$qtype,$course,$isTrue,$points,$difficulty);
    $_SESSION["info"][] = 'Question Successfully Added';
    if ($qtype == 0) {
        foreach ($_POST['MCQanswer'] as $key=>$qanswer) {
            $answer = !empty($qanswer['answertext']) ? trim($qanswer['answertext']) : null;
            $isCorrect = !empty($qanswer['isCorrect']) ? 1 : 0;
            if ($answer != null) {
                $newQuestion->insertAnswersToLast($answer, $isCorrect,null);
            }
        }
    } elseif ($qtype == 3) {
      foreach ($_POST['MSQanswer'] as $key=>$qanswer) {
          $answer = !empty($qanswer['answertext']) ? trim($qanswer['answertext']) : null;
          $isCorrect = !empty($qanswer['isCorrect']) ? 1 : 0;
          if ($answer != null) {
              $newQuestion->insertAnswersToLast($answer, $isCorrect,null);
          }
      }
    }elseif ($qtype == 2) {
        foreach ($_POST['Canswer'] as $key=>$canswer) {
            $answer = $canswer['answertext'];
            if ($answer != '') {
                $newQuestion->insertAnswersToLast($answer, 1, null);
            }
        }
    }elseif ($qtype == 4) {
        foreach ($_POST['match'] as $key=>$manswer) {
            $matchAnswer = $_POST['matchAnswer'][$key];
            $matchPoints = $_POST['matchPoints'][$key];
            $answer = $manswer;
            if ($manswer != '' and $matchAnswer != '') {
                $newQuestion->insertAnswersToLast($manswer, 1, $matchAnswer,$matchPoints);
            }
        }
    }
    header('Location: ../../?questions=add&topic=' . $course);exit;
} elseif (isset($_GET['deleteQuestion'])) {
    $qst = new question;
    $qst->setQuestionDelete($_GET['deleteQuestion']);
    header('Location: ../../?questions');
} elseif (isset($_GET['restoreQuestion'])) {
    $qst = new question;
    $qst->restoreQuestion($_GET['restoreQuestion']);
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} elseif (isset($_GET['PDeleteQuestion'])) {
    $qst = new question;
    $qst->pDeleteQuestion($_GET['PDeleteQuestion']);
    header('Location: ' . $_SERVER['HTTP_REFERER']);

} elseif (isset($_GET['updateQuestion'])) {
    $id = isset($_POST['qid']) ? trim($_POST['qid']) : null;
    $question = isset($_POST['questionText']) ? trim($_POST['questionText']) : null;
    $qtype = isset($_POST['qtype']) ? trim($_POST['qtype']) : 0;
    $isTrue = isset($_POST['isTrue']) ? trim($_POST['isTrue']) : 0;
    $points = isset($_POST['points']) ? trim($_POST['points']) : 0;
    $difficulty = isset($_POST['difficulty']) ? trim($_POST['difficulty']) : 1;
    $course = $_POST['Course'];

    $newQuestion = new question;
    $newQuestion->updateQuestion($id,$question,$course,$points,$difficulty);
    $newQuestion->updateTF($id, $isTrue);

    if ($qtype == 0 || $qtype == 3) {
        foreach ($_POST['Qanswer'] as $key=>$qanswer) {
            $ansID = isset($qanswer['ansID']) ? trim($qanswer['ansID']) : null;
            $answer = !empty($qanswer['answertext']) ? trim($qanswer['answertext']) : null;
            $isCorrect = !empty($qanswer['isCorrect']) ? trim($qanswer['isCorrect']) : 0;
            if ($ansID == null) {
                if ($answer != null) {
                    $newQuestion->insertAnswers($id, $answer, $isCorrect);
                }
              } else {
                $newQuestion->updateAnswer($ansID, $answer, $isCorrect,null);
            }
        }
    } elseif ($qtype == 2) {
        foreach ($_POST['Canswer'] as $key=>$canswer) {
            $answer = $canswer['answertext'];
            if ($answer != '') {
                $newQuestion->insertAnswers($id,$answer,1);
            }
        }
    } elseif ($qtype == 4) {
      foreach ($_POST['match'] as $key=>$manswer) {
          $oldAns = isset($_POST['oldID'][$key]) ? $_POST['oldID'][$key] : null;
          $matchAnswer = $_POST['matchAnswer'][$key];
          $matchPoints = $_POST['matchPoints'][$key];
          if ($manswer != '' and $matchAnswer != '') {
            if($oldAns == null){
              $newQuestion->insertAnswers($id,$manswer,1,$matchAnswer,$matchPoints);
            }else{
              $newQuestion->updateAnswer($oldAns, $manswer, 1,$matchAnswer,$matchPoints);
            }
          }
      }
    }
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}elseif (isset($_GET['duplicateQuestion']) and is_numeric($_GET['duplicateQuestion'])){
        $id = $_GET['duplicateQuestion'];
        $q = new question;
        $q->duplicateQuestion($id);
        $newID = $q->getLastQuestion()->id;
        header('Location:../../?questions=view&id='. $newID);
}else if (isset($_GET['export'])) {
    try {
        ob_end_clean(); // Clear any previous output

        // Check if the course name is provided
        if (!isset($_POST['course']) || empty($_POST['course'])) {
            throw new Exception("Course not specified.");
        }

        $course = $_POST['course'];
        $q = new question; 
        $questions = $q->getByCourse($course);

        if (empty($questions)) {
            throw new Exception("No questions found for this course.");
        }

        $qTypes = [
            0 => 'Multiple Choice', 1 => 'True/False', 2 => 'Complete',
            3 => 'Multiple Select', 4 => 'Matching', 5 => 'Essay'
        ];

        $data = [];
        foreach ($questions as $question) {
            $id = $question->id;
            $quest = strip_tags($question->question);
            $type = $question->type;
            $difficulty = $question->difficulty;
            $typetext = $qTypes[$type];
            $points = $question->points;
            $isTrue = $question->isTrue;

            $answers = $q->getQuestionAnswers($id);
            $ans1 = $answers[0]->answer ?? '';
            $ans2 = $answers[1]->answer ?? '';
            $ans3 = $answers[2]->answer ?? '';
            $ans4 = $answers[3]->answer ?? '';

            if ($type == 0 || $type == 3) { // Multiple Choice & Multiple Select
                $ans1 = ($answers[0]->isCorrect ?? false) ? "#!$ans1" : $ans1;
                $ans2 = ($answers[1]->isCorrect ?? false) ? "#!$ans2" : $ans2;
                $ans3 = ($answers[2]->isCorrect ?? false) ? "#!$ans3" : $ans3;
                $ans4 = ($answers[3]->isCorrect ?? false) ? "#!$ans4" : $ans4;
            } elseif ($type == 4) { // Matching
                $ans1 = isset($answers[0]) ? "{$answers[0]->answer} >> {$answers[0]->matchAnswer}" : '';
                $ans2 = isset($answers[1]) ? "{$answers[1]->answer} >> {$answers[1]->matchAnswer}" : '';
                $ans3 = isset($answers[2]) ? "{$answers[2]->answer} >> {$answers[2]->matchAnswer}" : '';
                $ans4 = isset($answers[3]) ? "{$answers[3]->answer} >> {$answers[3]->matchAnswer}" : '';
            } elseif ($type == 1) { // True/False
                $ans1 = ($isTrue == 1) ? 'True' : 'False';
                $ans2 = $ans3 = $ans4 = '';
            } elseif ($type == 5) { // Essay
                $ans1 = $ans2 = $ans3 = $ans4 = '';
            }

            $data[] = [$quest, $typetext, $points, $difficulty, $ans1, $ans2, $ans3, $ans4];
        }

        // Create a new Excel file
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add headers
        $headers = ['Question', 'Question Type', 'Points', 'Difficulty', 'Answer 1', 'Answer 2', 'Answer 3', 'Answer 4'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Style headers
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']]
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // Add data
        $row = 2;
        foreach ($data as $dataRow) {
            $col = 'A';
            foreach ($dataRow as $cell) {
                $sheet->setCellValue($col . $row, $cell);
                $col++;
            }

            // Alternate row styling
            if ($row % 2 == 0) {
                $sheet->getStyle("A$row:H$row")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E9E9E9');
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'H') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Prepare the file for download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $course . '_Questions.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;

    } catch (Exception $e) {
        die("An error occurred: " . $e->getMessage());
    }
}else if (isset($_GET['import']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من وجود ملف مرفوع
    if (!isset($_FILES['excel']) || $_FILES['excel']['error'] !== UPLOAD_ERR_OK) {
        die(json_encode([
            'status' => 'error',
            'message' => 'لم يتم رفع الملف أو حدث خطأ في الرفع: ' . $_FILES['excel']['error']
        ]));
    }

    $excelFile = $_FILES['excel']['tmp_name'];

    // التحقق من صحة الملف
    if (!file_exists($excelFile)) {
        die(json_encode([
            'status' => 'error',
            'message' => 'الملف غير موجود على الخادم'
        ]));
    }

    if (!is_readable($excelFile)) {
        die(json_encode([
            'status' => 'error',
            'message' => 'لا يمكن قراءة الملف'
        ]));
    }

    try {
        // تحديد نوع الملف وإنشاء القارئ المناسب
        $reader = IOFactory::createReaderForFile($excelFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($excelFile);

        // الحصول على البيانات من الورقة الأولى
        $sheet = $spreadsheet->getActiveSheet();
        $sheetData = $sheet->toArray(null, true, true, true);

        if (empty($sheetData)) {
            die(json_encode([
                'status' => 'error',
                'message' => 'ملف Excel فارغ'
            ]));
        }

        // تضمين ملف نموذج الأسئلة
        require_once '../model/question.class.php';
        if (!class_exists('question')) {
            die(json_encode([
                'status' => 'error',
                'message' => 'فئة الأسئلة غير موجودة'
            ]));
        }

        $q = new question();
        $course = $_POST['course'] ?? null;

        if (!$course) {
            die(json_encode([
                'status' => 'error',
                'message' => 'لم يتم تحديد المقرر الدراسي'
            ]));
        }

        // أنواع الأسئلة المتاحة
        $qTypes = [
            'Multiple Choice' => 0,
            'True/False' => 1,
            'Complete' => 2,
            'Multiple Select' => 3,
            'Matching' => 4,
            'Essay' => 5
        ];

        // بدء عملية الاستيراد
        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        // تفعيل التعامل مع المعاملات إذا كانت مدعومة
        $transactionSupported = method_exists($q, 'beginTransaction');
        if ($transactionSupported) {
            $q->beginTransaction();
        }

        foreach ($sheetData as $rowIndex => $row) {
            // تخطي الصفوف الأولى (العناوين)
            if ($rowIndex < 2) continue;

            $questionText = trim($row['A'] ?? '');
            $questionTypeText = trim($row['B'] ?? '');
            $points = (int)trim($row['C'] ?? 0);
            $difficulty = (int)trim($row['D'] ?? 1);

            // التحقق من صحة البيانات الأساسية
            if (empty($questionText)) {
                $errors[] = "سطر $rowIndex: نص السؤال فارغ";
                $skippedCount++;
                continue;
            }

            if (!isset($qTypes[$questionTypeText])) {
                $errors[] = "سطر $rowIndex: نوع السؤال غير صحيح '$questionTypeText'";
                $skippedCount++;
                continue;
            }

            $qtype = $qTypes[$questionTypeText];

            // إدخال السؤال الأساسي
            try {
                $insertResult = $q->insertQuestion($questionText, $qtype, $course, 0, $points, $difficulty);
                
                if (!$insertResult) {
                    $errors[] = "سطر $rowIndex: فشل إدخال السؤال في قاعدة البيانات";
                    $skippedCount++;
                    continue;
                }

                $lastInsertedId = $q->getLastQuestion()->id;
                $importedCount++;

                // معالجة الإجابات حسب نوع السؤال
                switch ($qtype) {
                    case 0: // Multiple Choice
                    case 3: // Multiple Select
                        for ($i = 'E'; $i <= 'H'; $i++) {
                            $answerText = trim($row[$i] ?? '');
                            if (empty($answerText)) continue;
                            
                            $isCorrect = strpos($answerText, '#!') === 0 ? 1 : 0;
                            $answerText = str_replace('#!', '', $answerText);
                            
                            if (!$q->insertAnswers($lastInsertedId, $answerText, $isCorrect)) {
                                $errors[] = "سطر $rowIndex: فشل إدخال الإجابة في العمود $i";
                            }
                        }
                        break;
                        
                    case 1: // True/False
                        $isTrue = strtolower(trim($row['E'] ?? '')) === 'true' ? 1 : 0;
                        if (!$q->updateTF($lastInsertedId, $isTrue)) {
                            $errors[] = "سطر $rowIndex: فشل تحديث إجابة صح/خطأ";
                        }
                        break;
                        
                    case 2: // Complete
                        for ($i = 'E'; $i <= 'H'; $i++) {
                            $answerText = trim($row[$i] ?? '');
                            if (!empty($answerText)) {
                                if (!$q->insertAnswers($lastInsertedId, $answerText, 1)) {
                                    $errors[] = "سطر $rowIndex: فشل إدخال إجابة التكميل في العمود $i";
                                }
                            }
                        }
                        break;
                        
                    case 4: // Matching
                        for ($i = 'E'; $i <= 'H'; $i++) {
                            $matchText = trim($row[$i] ?? '');
                            if (empty($matchText)) continue;
                            
                            $matchParts = explode('>>', $matchText);
                            if (count($matchParts) !== 2) {
                                $errors[] = "سطر $rowIndex: تنسيق المطابقة غير صحيح في العمود $i";
                                continue;
                            }
                            
                            if (!$q->insertAnswers($lastInsertedId, trim($matchParts[0]), 1, trim($matchParts[1]))) {
                                $errors[] = "سطر $rowIndex: فشل إدخال المطابقة في العمود $i";
                            }
                        }
                        break;
                        
                    case 5: // Essay
                        // لا تحتاج لإجابات محددة
                        break;
                }
            } catch (Exception $e) {
                $errors[] = "سطر $rowIndex: " . $e->getMessage();
                $skippedCount++;
                continue;
            }
        }

        // إذا كان التعامل مع المعاملات مدعومًا، تأكيد العملية
        if ($transactionSupported) {
            $q->commit();
        }

        // نتيجة الاستيراد
        $result = [
            'status' => 'success',
            'imported' => $importedCount,
            'skipped' => $skippedCount,
            'errors' => $errors,
            header('Location: ../../?questions')

        ];

        echo json_encode($result);
        exit;

    } catch (Exception $e) {
        // في حالة حدوث خطأ، التراجع عن المعاملة إذا كانت مدعومة
        if ($transactionSupported && isset($q)) {
            $q->rollBack();
        }

        die(json_encode([
            'status' => 'error',
            'message' => 'حدث خطأ أثناء معالجة الملف: ' . $e->getMessage()
        ]));
    }
} else {
    die(json_encode([
        'status' => 'error',
        'message' => 'طلب غير صالح'
    ]));
}

  
?>