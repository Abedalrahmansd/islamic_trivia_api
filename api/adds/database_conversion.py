import json

def clean_text(text):
    """Clean text for SQL insertion"""
    if not text:
        return ""
    text = str(text).strip()
    # Escape single quotes for SQL
    text = text.replace("'", "''")
    return text

def get_category_mapping():
    """Map Arabic category names to database category IDs"""
    return {
        "التفسير": "@tafseer_category_id",
        "العقيدة": "@akida_category_id", 
        "الحديث": "@hadith_category_id",
        "الفقه": "@fiqh_category_id",
        "السيرة": "@seerah_category_id",
        "التاريخ": "@history_category_id",
        "الأخلاق": "@akhlaq_category_id",
        "القرآن": "@quran_category_id"
    }

def get_difficulty_by_level(level):
    """Convert level to difficulty"""
    level_mapping = {
        "level1": "easy",
        "level2": "medium", 
        "level3": "hard"
    }
    return level_mapping.get(level, "medium")

def convert_islamic_quiz_to_sql(json_file_path, output_file_path=None):
    """
    Convert the specific Islamic quiz JSON format to SQL INSERT statements
    
    Args:
        json_file_path (str): Path to the JSON file
        output_file_path (str): Output SQL file path (optional)
    
    Returns:
        str: SQL INSERT statements
    """
    
    # Read JSON file
    try:
        with open(json_file_path, 'r', encoding='utf-8') as file:
            data = json.load(file)
        print("✓ File loaded successfully")
    except Exception as e:
        print(f"✗ Error reading file: {e}")
        return ""
    
    category_mapping = get_category_mapping()
    sql_statements = []
    total_questions = 0
    
    # Process main categories
    if "mainCategories" not in data:
        print("✗ No 'mainCategories' found in JSON")
        return ""
    
    for category in data["mainCategories"]:
        category_name = category.get("arabicName", "")
        category_id = category_mapping.get(category_name, f"@{category.get('englishName', 'unknown')}_category_id")
        
        print(f"Processing category: {category_name} -> {category_id}")
        
        # Process topics within category
        topics = category.get("topics", [])
        
        for topic in topics:
            topic_name = topic.get("name", "")
            levels_data = topic.get("levelsData", {})
            
            print(f"  Processing topic: {topic_name}")
            
            # Process each level
            for level, questions in levels_data.items():
                if not isinstance(questions, list):
                    continue
                
                difficulty = get_difficulty_by_level(level)
                
                print(f"    Processing {level} ({difficulty}): {len(questions)} questions")
                
                # Process each question
                for question in questions:
                    sql = process_single_question(question, category_id, difficulty)
                    if sql:
                        sql_statements.append(sql)
                        total_questions += 1
    
    # Combine results
    result = '\n'.join(sql_statements)
    
    # Save to file if specified
    if output_file_path:
        try:
            with open(output_file_path, 'w', encoding='utf-8') as file:
                file.write(result)
            print(f"✓ Results saved to: {output_file_path}")
        except Exception as e:
            print(f"✗ Error saving file: {e}")
    
    print(f"✓ Generated {total_questions} SQL statements")
    return result

def process_single_question(question, category_id, difficulty):
    """Process a single question and convert to SQL"""
    try:
        # Extract question text
        question_text_ar = clean_text(question.get("q", ""))
        if not question_text_ar:
            return None
        
        # Extract answers
        answers = question.get("answers", [])
        if len(answers) < 3:
            print(f"    Warning: Question has less than 3 answers: {question_text_ar[:50]}...")
            return None
        
        # Process options and find correct answer
        options_ar = []
        correct_answer = 'a'  # default
        
        for i, answer in enumerate(answers[:4]):  # Take max 4 answers
            answer_text = clean_text(answer.get("answer", ""))
            options_ar.append(answer_text)
            
            # Check if this is the correct answer (t = 1)
            if answer.get("t") == 1:
                correct_answer = chr(ord('a') + i)  # Convert 0,1,2,3 to a,b,c,d
        
        # Ensure we have 4 options (fill empty ones)
        while len(options_ar) < 4:
            options_ar.append("")
        
        # Create SQL statement
        sql = f"""INSERT INTO questions (category_id, question_text, question_text_ar, option_a, option_a_ar, option_b, option_b_ar, option_c, option_c_ar, option_d, option_d_ar, correct_answer, difficulty) VALUES
({category_id}, '', '{question_text_ar}', '', '{options_ar[0]}', '', '{options_ar[1]}', '', '{options_ar[2]}', '', '{options_ar[3]}', '{correct_answer}', '{difficulty}'),"""
        
        return sql
        
    except Exception as e:
        print(f"    Error processing question: {e}")
        return None

# Example usage
if __name__ == "__main__":
    # Convert the JSON file
    json_file = "IslamicDB.json"
    output_file = "islamic_quiz_output.sql"
    
    sql_output = convert_islamic_quiz_to_sql(json_file, output_file)
    
    # Show sample output
    if sql_output:
        lines = [line for line in sql_output.split('\n') if line.strip()]
        print(f"\n=== Sample Output (first 2 statements) ===")
        for i, line in enumerate(lines[:2]):
            print(f"{i+1}. {line}")
    else:
        print("No output generated")