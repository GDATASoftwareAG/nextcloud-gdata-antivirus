package main

import (
	"fmt"
	"log"
	"os"
	"os/exec"
)

func main() {
	for i := 0; i <= 200; i++ {
		// Format directory name to four digits
		dirName := fmt.Sprintf("%04d", i)

		// Create the directory
		err := os.MkdirAll(dirName, 0755)
		if err != nil {
			log.Fatalf("Failed to create directory %s: %v", dirName, err)
		}

		// Change to the directory
		err = os.Chdir(dirName)
		if err != nil {
			log.Fatalf("Failed to change directory to %s: %v", dirName, err)
		}

		// Create 10,000 empty files
		for j := 0; j <= 9999; j++ {
			fileName := fmt.Sprintf("%04d", j)
			file, err := os.Create(fileName)
			if err != nil {
				log.Fatalf("Failed to create file %s: %v", fileName, err)
			}
			file.Close()
		}

		// Change back to the parent directory
		err = os.Chdir("..")
		if err != nil {
			log.Fatalf("Failed to change directory to parent: %v", err)
		}
	}

	// Use the chown command line tool to change ownership of all created files and directories to www-data:www-data
	cmd := exec.Command("chown", "-R", "www-data:www-data", ".")
	err := cmd.Run()
	if err != nil {
		log.Fatalf("Failed to change ownership: %v", err)
	}

	fmt.Println("Empty files created successfully.")
}
