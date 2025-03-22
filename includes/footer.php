</main>
    <!-- Footer Section -->
    <footer class="bg-gradient-to-r from-blue-800 to-blue-900 text-white py-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- قسم اللغات المتوفرة -->
                <?php
                /**
                 * إعداد الاتصال بقاعدة البيانات
                 * يتم استخدام PDO للاتصال الآمن بقاعدة البيانات
                 */
                try {
                    $pdo = new PDO(
                        "mysql:host=localhost;dbname=courses_db;charset=utf8",
                        "root",
                        "",
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                        ]
                    );

                    /**
                     * استعلام لجلب اللغات التي لديها دروس
                     * يتم جلب اللغات التي لديها دروس فقط من خلال الربط مع جدول الدروس
                     */
                    $stmt = $pdo->query("
                        SELECT DISTINCT l.id, l.name, COUNT(les.id) as lesson_count 
                        FROM languages l 
                        INNER JOIN lessons les ON les.course_id IN (
                            SELECT id FROM courses WHERE language_id = l.id
                        )
                        GROUP BY l.id, l.name
                        ORDER BY l.name ASC
                    ");
                    $languages = $stmt->fetchAll();
                } catch(PDOException $e) {
                    // تسجيل الخطأ بشكل آمن
                    error_log("Database Error: " . $e->getMessage());
                    $languages = []; // مصفوفة فارغة في حالة حدوث خطأ
                }
                ?>
                <div>
                    <h5 class="text-xl font-bold mb-4">اللغات المتوفرة</h5>
                    <div class="grid grid-cols-2 gap-2">
                        <?php foreach ($languages as $language): ?>
                            <a href="http://videomx.com/sections/index.php?language_id=<?php echo htmlspecialchars($language['id']); ?>" 
                               class="hover:text-blue-200 flex items-center bg-blue-700 bg-opacity-30 rounded-lg p-2 transition-all hover:bg-opacity-50"
                               title="عرض دروس <?php echo htmlspecialchars($language['name']); ?>">
                                <i class="fas fa-code ml-2"></i>
                                <span><?php echo htmlspecialchars($language['name']); ?></span>
                                <span class="mr-auto text-xs bg-blue-600 px-2 py-1 rounded-full" 
                                      title="عدد الدروس المتوفرة">
                                    <?php echo $language['lesson_count']; ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- قسم روابط الدروس -->
                <div>
                    <h5 class="text-xl font-bold mb-4">عرض الدروس</h5>
                    <ul class="space-y-2">
                        <li>
                            <a href="/views/lessons-cards.php" class="hover:text-blue-200 flex items-center">
                                <i class="fas fa-th-large ml-2"></i>
                                عرض البطاقات
                            </a>
                        </li>
                        <li>
                            <a href="/lessons.php" class="hover:text-blue-200 flex items-center">
                                <i class="fas fa-list ml-2"></i>
                                عرض القائمة
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- قسم الروابط الرئيسية -->
                <div>
                    <h5 class="text-xl font-bold mb-4">روابط رئيسية</h5>
                    <ul class="space-y-2">
                        <li>
                            <a href="http://videomx.com/content/languages.php" class="hover:text-blue-200 flex items-center">
                                <i class="fas fa-globe ml-2"></i>
                                اللغات
                            </a>
                        </li>
                        <li>
                            <a href="/" class="hover:text-blue-200 flex items-center">
                                <i class="fas fa-home ml-2"></i>
                                الرئيسية
                            </a>
                        </li>
                        <li>
                            <a href="http://videomx.com/content/index.php" class="hover:text-blue-200 flex items-center">
                                <i class="fas fa-graduation-cap ml-2"></i>
                                الدورات
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- قسم الروابط الإضافية -->
                <div>
                    <h5 class="text-xl font-bold mb-4">روابط إضافية</h5>
                    <ul class="space-y-2">
                        <li>
                            <a href="http://videomx.com/add/add.php" class="hover:text-blue-200 flex items-center">
                                <i class="fas fa-cog ml-2"></i>
                                الإعدادات
                            </a>
                        </li>
                        <li>
                            <a href="http://videomx.com/GBT/" class="hover:text-blue-200 flex items-center">
                                <i class="fas fa-robot ml-2"></i>
                                المساعد الذكي
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <hr class="my-6 border-blue-700">

            <!-- Social Links -->
            <div class="text-center">
                <div class="flex justify-center space-x-4 space-x-reverse mb-4">
                    <a href="#" class="hover:text-blue-200" title="فيسبوك"><i class="fab fa-facebook fa-lg"></i></a>
                    <a href="#" class="hover:text-blue-200" title="تويتر"><i class="fab fa-twitter fa-lg"></i></a>
                    <a href="#" class="hover:text-blue-200" title="يوتيوب"><i class="fab fa-youtube fa-lg"></i></a>
                    <a href="#" class="hover:text-blue-200" title="انستغرام"><i class="fab fa-instagram fa-lg"></i></a>
                    <a href="#" class="hover:text-blue-200" title="لينكد إن"><i class="fab fa-linkedin fa-lg"></i></a>
                    <a href="#" class="hover:text-blue-200" title="جيت هب"><i class="fab fa-github fa-lg"></i></a>
                </div>
                <p class="text-sm">جميع الحقوق محفوظة © 2024 VideoMX</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Font Awesome -->
    <script src="https://kit.fontawesome.com/your-kit-code.js" crossorigin="anonymous"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        // توثيق الدوال والوظائف
        /**
         * دالة للتحقق من صحة الروابط
         * @param {string} url - الرابط المراد التحقق منه
         * @returns {boolean} - يعيد true إذا كان الرابط صحيح
         */
        function validateUrl(url) {
            try {
                new URL(url);
                return true;
            } catch {
                return false;
            }
        }

        // مثال على استخدام الدالة
        document.querySelectorAll('a').forEach(link => {
            if (!validateUrl(link.href)) {
                console.warn(`رابط غير صالح: ${link.href}`);
            }
        });
    </script>
</body>
</html>