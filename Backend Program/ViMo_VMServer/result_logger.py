import os
import csv
import shutil
from folder_organizer import get_image_paths

def save_results_to_csv(csv_path, headers, rows):
    try:
        with open(csv_path, 'w', newline='', encoding='utf-8') as f:
            writer = csv.writer(f)
            writer.writerow(headers)
            writer.writerows(rows)
        print(f"✅ [CSV] {csv_path}")
    except Exception as e:
        print(f"❌ [CSV ERROR] {csv_path}: {e}")

def load_csv_to_dict(csv_path, key_column):
    data = {}
    if not os.path.exists(csv_path):
        print(f"⚠️ CSV tidak ditemukan: {csv_path}")
        return data

    try:
        with open(csv_path, 'r', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            for row in reader:
                key = os.path.splitext(row.get(key_column, ""))[0]
                data[key] = row
    except Exception as e:
        print(f"❌ [LOAD CSV ERROR] {e}")

    return data

def post_processing(pre_dir, recog_dir, expr_dir, out_dir):
    image_paths = get_image_paths(pre_dir)
    print(f"🔍 {len(image_paths)} gambar ditemukan.")

    recog_data = load_csv_to_dict(os.path.join(recog_dir, "recognition_results.csv"), "filename")
    expr_data = load_csv_to_dict(os.path.join(expr_dir, "expression_results.csv"), "filename")

    output_csv = os.path.join(out_dir, "final_results.csv")
    headers = ["filename", "predicted_name", "recognition_confidence", "predicted_emotion", "expression_confidence"]
    rows = []

    for img_path in image_paths:
        base = os.path.splitext(os.path.basename(img_path))[0]
        recog = recog_data.get(base, {})
        expr = expr_data.get(base, {})

        name = recog.get("predicted_name", "Unknown")
        name_conf = recog.get("confidence", "0")
        emotion = expr.get("predicted_label", "unknown")
        emotion_conf = expr.get("confidence", "0")

        new_name = f"{base}_{name}_{emotion}.jpg"
        new_path = os.path.join(out_dir, new_name)

        try:
            shutil.copy(img_path, new_path)
            print(f"💾 {new_name}")
        except Exception as e:
            print(f"❌ Copy gagal: {img_path} -> {new_path}: {e}")
            continue

        rows.append([new_name, name, name_conf, emotion, emotion_conf])

    save_results_to_csv(output_csv, headers, rows)
    return output_csv