

<template>

</template>

<script>
export default {
  name: 'DirectivesLesson',
  data() {
    return {
      items: ['تفاح', 'موز', 'برتقال'],
      person: {
        name: 'أحمد',
        age: 30,
        job: 'مطور'
      },
      count: 3,
      isVisible: true,
      imageUrl: 'https://picsum.photos/200',
      imageAlt: 'صورة عشوائية',
      imageWidth: 200,
      counter: 0
    }
  },
  methods: {
    addItem() {
      const fruits = ['كيوي', 'مانجو', 'فراولة', 'عنب', 'خوخ'];
      const randomFruit = fruits[Math.floor(Math.random() * fruits.length)];
      this.items.push(randomFruit);
    },
    removeItem() {
      if (this.items.length > 0) {
        this.items.pop();
      }
    },
    toggleVisibility() {
      this.isVisible = !this.isVisible;
    },
    changeImage() {
      // تغيير الصورة بإضافة رقم عشوائي لتجنب التخزين المؤقت
      this.imageUrl = `https://picsum.photos/200?random=${Math.random()}`;
      this.imageAlt = `صورة عشوائية ${Math.floor(Math.random() * 100)}`;
    }
  }
}
</script>

<style scoped>
.directives-lesson {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
  direction: rtl;
  text-align: right;
}

section {
  margin-bottom: 30px;
  border-bottom: 1px solid #eee;
  padding-bottom: 20px;
}

h1 {
  color: #42b983;
  margin-bottom: 20px;
}

h2 {
  color: #2c3e50;
  margin-top: 20px;
  margin-bottom: 10px;
}

h3 {
  color: #486491;
  margin-top: 15px;
  margin-bottom: 10px;
}

.example {
  background-color: #f8f8f8;
  padding: 15px;
  border-radius: 5px;
  margin-top: 10px;
}

.result {
  background-color: white;
  padding: 15px;
  border: 1px solid #ddd;
  border-radius: 3px;
  margin-bottom: 15px;
}

code {
  display: block;
  background-color: #2c3e50;
  color: #eee;
  padding: 10px;
  border-radius: 3px;
  margin: 10px 0;
  white-space: pre-wrap;
  direction: ltr;
  text-align: left;
}

.input-group {
  margin-top: 15px;
  display: flex;
  gap: 10px;
  align-items: center;
}

button {
  background-color: #42b983;
  color: white;
  border: none;
  padding: 5px 10px;
  border-radius: 3px;
  cursor: pointer;
}

button:hover {
  background-color: #3aa876;
}

button:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

img {
  max-width: 100%;
  height: auto;
  display: block;
  margin: 0 auto;
}
</style> 