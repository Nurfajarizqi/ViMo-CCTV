<h1 align="center">ViMo Camera : A Smart Camera for Detecting Classroom Attendance and Students Positive-Negative 
Emotions</h1>

<p align="center">
  <img src="https://img.shields.io/badge/Object%20Detection-YOLOv8n-magenta?style=for-the-badge"/>
  <img src="https://img.shields.io/badge/Face%20Detection-MTCNN-crimson?style=for-the-badge"/>
  <img src="https://img.shields.io/badge/Face Recognition-FaceNet-dodgerblue?style=for-the-badge"/>
  <img src="https://img.shields.io/badge/Facial Expression Recognition-EfficienNetB3-yellow?style=for-the-badge"/>
  <img src="https://img.shields.io/badge/Classification-SVM-cornsilk?style=for-the-badge"/>
</p>

---

### **👥 Team : EL3**

**Members:**
- Muhammad Abyan Nurfajarizqi (muhammadabyan077@gmail.com)  
- Muhammad Hafidz Hidayatullah (hafidzhidayatullah1012@gmail.com)

**Origin:** Universitas Islam Indonesia

---

## 📌 **Project Overview**

> *"Detect student using Object Detection, Identifying Student using Face Recognition and Identifying Emotion using Facial Expression Recognition"*

The system operates on a Raspberry Pi 5 with a Camera Module 3 Wide. Object detection is executed directly on the Raspberry Pi, while Face Recognition (FR) and Facial Expression Recognition (FER) processes are performed on a dedicated server running on VMware for higher computational efficiency.

---

## 🖼️ **Pipeline**
<img width="714" height="463" alt="Pipeline_ViMoCCTV drawio" src="https://github.com/user-attachments/assets/ecb29049-11a1-4fff-b264-961baf364c8e" />


---


### 🔧 **Technical Implementation**
### 1. **Object Detection – YOLOv8n**
- **Purpose:** Detect student from classroom CCTV footage in real time  
- **Advantages:** Lightweight, optimized for real-time inference  
- **Input:** CCTV video stream frame-by-frame  
- **Output:** Cropped object (person) regions with bounding box coordinates  

### 2. **Face Recognition – FaceNet + SVM**
- **Purpose:** Generate 128-dimensional embedding vectors for each face  
- **Function:** Compare face embeddings with the student database  
- **Output:** Student identity + confidence score  

### 3. **Facial Expression Recognition (FER) – EfficientNetB3 + SVM**
- **Purpose:** Classify whether a student’s emotion is positive or negative  
- **Advantages:** Lightweight and Fast   
- **Output:** Emotion label + confidence score  


---

## 🖥️ Web Application Features

| **Feature**      | **Description** |
|------------------|-----------------|
| **Live Streaming**    | Displays real-time classroom video with Object Detection |
| **Pie Chart**    | Shows emotion distribution (positive vs negative) and attendance statistics in real time |
| **Download Recap**  | Allows users to download attendance and emotion monitoring reports in .xlxs format |
| **Multi Role Access** | Provides different access levels for Admin, Teachers, and School Executives with customized permissions |


---

## 🧾 **Result**
<img width="1920" height="880" alt="Live Page" src="https://github.com/user-attachments/assets/2f565e06-1b52-4e15-a04b-a70a7e8dfae5" />
<img width="1920" height="880" alt="7" src="https://github.com/user-attachments/assets/2ab8bc27-91e5-4c2a-a017-6a46e5ff83d4" />


---
